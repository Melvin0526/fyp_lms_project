<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection and room functions
include 'config.php';
include 'room_functions.php';

$username = $_SESSION['username'];
$email = $_SESSION['email'];
$user_id = $_SESSION['user_id'];

// Update reservation statuses based on current time
updateReservationStatuses($conn);

// Process reservation cancellation
$notification_message = "";
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservation_id = $_GET['cancel'];
    $result = cancelReservation($conn, $reservation_id, $user_id);
    
    if ($result === true) {
        $notification_message = '<div class="success-message"><i class="fas fa-check-circle"></i> Reservation cancelled successfully!</div>';
    } else {
        $notification_message = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' . $result . '</div>';
    }
}

// Get user's preferences
$user_preferences = [];
$preferences_query = "SELECT p.category_id, c.name as category_name 
                     FROM user_preferences p
                     JOIN categories c ON p.category_id = c.category_id
                     WHERE p.id = ?";
$stmt = $conn->prepare($preferences_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$preferences_result = $stmt->get_result();

while ($pref = $preferences_result->fetch_assoc()) {
    $user_preferences[] = $pref;
}

// Get all available categories
$all_categories = [];
$categories_query = "SELECT category_id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);

while ($cat = $categories_result->fetch_assoc()) {
    $all_categories[] = $cat;
}

// Get user's room reservations (limit to 5 most recent)
$user_reservations = getUserReservations($conn, $user_id, 5);

// Get new arrival books (4 most recent)
$new_arrivals = [];
$new_arrivals_sql = "SELECT b.*, c.name AS category_name 
                    FROM books b 
                    LEFT JOIN categories c ON b.category_id = c.category_id 
                    WHERE b.status = 'available' AND b.available_copies > 0
                    ORDER BY b.date_added DESC LIMIT 4";
$new_arrivals_result = $conn->query($new_arrivals_sql);

if ($new_arrivals_result && $new_arrivals_result->num_rows > 0) {
    while ($book = $new_arrivals_result->fetch_assoc()) {
        if (empty($book['cover_image'])) {
            $book['cover_image'] = 'img/default-book-cover.png';
        }
        $new_arrivals[] = $book;
    }
}

// Get popular books (based on borrow frequency)
$popular_books = [];
$popular_books_sql = "SELECT b.*, c.name AS category_name, COUNT(l.loan_id) AS borrow_count 
                     FROM books b 
                     JOIN book_loans l ON b.book_id = l.book_id
                     LEFT JOIN categories c ON b.category_id = c.category_id 
                     WHERE b.status = 'available' AND b.available_copies > 0
                     GROUP BY b.book_id
                     ORDER BY borrow_count DESC 
                     LIMIT 4";
$popular_books_result = $conn->query($popular_books_sql);

if ($popular_books_result && $popular_books_result->num_rows > 0) {
    while ($book = $popular_books_result->fetch_assoc()) {
        if (empty($book['cover_image'])) {
            $book['cover_image'] = 'img/default-book-cover.png';
        }
        $popular_books[] = $book;
    }
}

// Get recommended books based on user's borrow history or preferences
$recommended_books = [];
$recommendation_source = '';

// First, check if user has borrow history
$history_query = "SELECT DISTINCT b.category_id 
                  FROM book_loans l 
                  JOIN books b ON l.book_id = b.book_id 
                  WHERE l.id = ? 
                  LIMIT 3";
$stmt = $conn->prepare($history_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$history_result = $stmt->get_result();

$history_categories = [];
while ($row = $history_result->fetch_assoc()) {
    if (!empty($row['category_id'])) {
        $history_categories[] = $row['category_id'];
    }
}

// Check for favorite authors based on borrow history
$authors_query = "SELECT DISTINCT b.author 
                 FROM book_loans l 
                 JOIN books b ON l.book_id = b.book_id 
                 WHERE l.id = ? 
                 LIMIT 3";
$stmt = $conn->prepare($authors_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$authors_result = $stmt->get_result();

$favorite_authors = [];
while ($row = $authors_result->fetch_assoc()) {
    if (!empty($row['author'])) {
        $favorite_authors[] = $row['author'];
    }
}

// Combine history categories with user preferences if we don't have enough history categories
if (count($history_categories) < 3 && !empty($user_preferences)) {
    $pref_categories = array_column($user_preferences, 'category_id');
    
    // Add preference categories that aren't already in history categories
    foreach ($pref_categories as $cat_id) {
        if (!in_array($cat_id, $history_categories) && count($history_categories) < 3) {
            $history_categories[] = $cat_id;
        }
    }
    $recommendation_source = 'combined';
} else if (!empty($history_categories)) {
    $recommendation_source = 'history';
}

// If user has categories (from history or preferences) or favorite authors, use them for recommendations
if (!empty($history_categories) || !empty($favorite_authors)) {
    $conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($history_categories)) {
        $history_categories_str = implode(',', $history_categories);
        $conditions[] = "b.category_id IN ($history_categories_str)";
    }
    
    if (!empty($favorite_authors)) {
        $author_placeholders = implode(',', array_fill(0, count($favorite_authors), '?'));
        $conditions[] = "b.author IN ($author_placeholders)";
        $params = array_merge($params, $favorite_authors);
        $types .= str_repeat('s', count($favorite_authors));
    }
    
    $where_clause = implode(' OR ', $conditions);
    
    // CASE statement for determining match type
    $case_when = !empty($favorite_authors) ? 
        "CASE WHEN b.author IN (" . implode(',', array_fill(0, count($favorite_authors), '?')) . ") THEN 'author' ELSE 'category' END" :
        "'category'";
    
    $recommended_sql = "SELECT b.*, c.name AS category_name,
                        $case_when as match_type
                      FROM books b
                      LEFT JOIN categories c ON b.category_id = c.category_id
                      WHERE b.status = 'available' 
                      AND b.available_copies > 0
                      AND ($where_clause)
                      AND b.book_id NOT IN (
                          SELECT book_id FROM book_loans WHERE id = ?
                      )
                      ORDER BY " . (!empty($favorite_authors) ? "match_type, " : "") . "RAND()
                      LIMIT 4";
    
    // Add parameters
    if (!empty($favorite_authors)) {
        $params = array_merge($params, $favorite_authors);
        $types .= str_repeat('s', count($favorite_authors));
    }
    $params[] = $user_id;
    $types .= 'i';
    
    $stmt = $conn->prepare($recommended_sql);
    
    if (!empty($params)) {
        // Create dynamic parameter binding
        $bind_params = array($types);
        foreach ($params as $key => $value) {
            $bind_params[] = &$params[$key];
        }
        call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    }
    
    $stmt->execute();
    $recommended_result = $stmt->get_result();
    
    if ($recommended_result && $recommended_result->num_rows > 0) {
        while ($book = $recommended_result->fetch_assoc()) {
            if (empty($book['cover_image'])) {
                $book['cover_image'] = 'img/default-book-cover.png';
            }
            $book['recommendation_type'] = $book['match_type'];
            $recommended_books[] = $book;
        }
    }
}

// If no recommendations yet or not enough books, use preferences
if (empty($recommended_books) && !empty($user_preferences)) {
    $preference_ids = array_column($user_preferences, 'category_id');
    $preference_ids_str = implode(',', $preference_ids);
    
    $recommended_prefs_sql = "SELECT b.*, c.name AS category_name
                             FROM books b
                             LEFT JOIN categories c ON b.category_id = c.category_id
                             WHERE b.status = 'available' 
                             AND b.available_copies > 0
                             AND b.category_id IN ($preference_ids_str)
                             ORDER BY RAND()
                             LIMIT 4";
    
    $recommended_result = $conn->query($recommended_prefs_sql);
    
    if ($recommended_result && $recommended_result->num_rows > 0) {
        while ($book = $recommended_result->fetch_assoc()) {
            if (empty($book['cover_image'])) {
                $book['cover_image'] = 'img/default-book-cover.png';
            }
            $book['recommendation_type'] = 'preference';
            $recommended_books[] = $book;
        }
        $recommendation_source = 'preferences';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="room_reservation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Library System (LMS)</h1>
            </div>            
            <nav>
                <ul>
                    <li><a href="homepage.php" class="active">Home</a></li>
                    <li><a href="book_reservation.php">Browse Book</a></li>
                    <li><a href="borrow_history.php">Borrow History</a></li>
                    <li><a href="room_reservation.php">Room Reservation</a></li>
                    <li><a href="reservation_history.php">Reservation History</a></li>        
                </ul>
            </nav>
            <div class="user-menu">
                <div class="user-info">
                    <span><?php echo htmlspecialchars($username); ?></span>                    
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main>       
            <section class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                <p>Welcome to our library dashboard!</p>
                <?php if (!empty($notification_message)): ?>
                    <div class="notification-area">
                        <?php echo $notification_message; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <!-- User Preferences Section -->
            <section class="user-preferences">
                <h2><i class="fas fa-bookmark"></i> Select Your Reading Preferences</h2>
                <p>Choose up to 3 categories that interest you the most for personalized book recommendations</p>
                
                <div class="preferences-container">
                    <div class="categories-selection">
                        <?php foreach($all_categories as $category): ?>
                            <?php 
                                $isSelected = false;
                                foreach($user_preferences as $pref) {
                                    if($pref['category_id'] == $category['category_id']) {
                                        $isSelected = true;
                                        break;
                                    }
                                }
                            ?>
                            <div class="category-checkbox">
                                <input type="checkbox" id="category_<?php echo $category['category_id']; ?>" 
                                       class="preference-checkbox" 
                                       value="<?php echo $category['category_id']; ?>"
                                       <?php echo $isSelected ? 'checked' : ''; ?>>
                                <label for="category_<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="preferences-actions">
                        <p class="preferences-status">
                            <span id="selected-count"><?php echo count($user_preferences); ?></span>/3 categories selected
                        </p>
                        <button id="save-preferences" class="save-preferences-btn">Save Preferences</button>
                    </div>

                    <div id="preferences-message" class="preferences-message" style="display: none;"></div>
                </div>
            </section>
            
            <!-- Recent Room Reservations Section -->
            <section class="recent-activity">
                <h2><i class="fas fa-calendar-check"></i> Recent Room Reservations</h2>
                <p>Reservation cannot be cancelled once the timeslot booked is start</p>
                <?php if (empty($user_reservations)): ?>
                    <div class="no-reservations">
                        <i class="far fa-calendar-times fa-3x"></i>
                        <p>You have no active room reservations.</p>
                        <p>Reserve a room for your study groups or meetings.</p>
                        <a href="room_reservation.php" class="make-reservation-btn">Make Reservation</a>
                    </div>
                <?php else: ?>
                    <div class="reservation-table-container">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($user_reservations as $reservation): ?>
                                    <?php 
                                        $reservation_date = new DateTime($reservation['date']);
                                        $formatted_date = $reservation_date->format('M d, Y');
                                    
                                        // Format timeslot information
                                        $timeslot_info = "N/A";
                                        if (isset($reservation['start_time']) && isset($reservation['end_time'])) {
                                            $time_start = new DateTime($reservation['start_time']);
                                            $time_end = new DateTime($reservation['end_time']);
                                            $timeslot_info = $time_start->format('g:i A') . ' - ' . $time_end->format('g:i A');
                                        } else if (isset($reservation['display_text'])) {
                                            $timeslot_info = htmlspecialchars($reservation['display_text']);
                                        }
                                    
                                        // Determine status class
                                        $status_class = '';
                                        switch($reservation['status']) {
                                            case 'confirmed':
                                                $status_class = 'ongoing';
                                                break;
                                            case 'completed':
                                                $status_class = 'completed';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'cancelled';
                                                break;
                                            default:
                                                $status_class = 'ongoing';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['room_name']); ?></td>
                                        <td><?php echo $formatted_date; ?></td>
                                        <td><?php echo $timeslot_info; ?></td>
                                        <td><span class="status <?php echo $status_class; ?>"><?php echo ucfirst($reservation['status']); ?></span></td>
                                        <td>
                                            <?php if($reservation['status'] == 'confirmed'): ?>
                                                <?php 
                                                    // Check if the timeslot has already started
                                                    $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
                                                    $reservationDateTime = new DateTime($reservation['date'] . ' ' . $reservation['start_time'], new DateTimeZone('Asia/Kuala_Lumpur'));
                                                    $timeslotStarted = ($now >= $reservationDateTime);
                                                    
                                                    if($timeslotStarted): 
                                                ?>
                                                    <span class="no-cancel-note">Started</span>
                                                <?php else: ?>
                                                    <a href="?cancel=<?php echo $reservation['reservation_id']; ?>" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this reservation?');">Cancel</a>
                                                <?php endif; ?>
                                            <?php elseif($reservation['status'] == 'completed'): ?>
                                                <span class="completed-note">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <!-- New Arrivals Section -->
            <section class="featured-books">
                <h2><i class="fas fa-book"></i> New Arrivals</h2>
                <p>Check out our latest additions to the library collection</p>
                <?php if (empty($new_arrivals)): ?>
                    <div class="no-reservations">
                        <i class="fas fa-book fa-3x"></i>
                        <p>No new arrivals at the moment.</p>
                        <p>Check back soon for newly added books!</p>
                        <a href="book_reservation.php" class="make-reservation-btn">Browse All Books</a>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach($new_arrivals as $book): ?>
                            <div class="book-card">
                                <div class="book-cover" style="background-image: url('<?php echo htmlspecialchars($book['cover_image']); ?>')"></div>
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p><?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="category-badge"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></p>
                                <button class="reserve-btn" data-book-id="<?php echo $book['book_id']; ?>">Reserve Now</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Popular Books Section -->
            <section class="featured-books popular-books">
                <h2><i class="fas fa-star"></i> Popular Books</h2>
                <p>Most frequently borrowed books that our readers love</p>
                <?php if (empty($popular_books)): ?>
                    <div class="no-reservations">
                        <i class="fas fa-star fa-3x"></i>
                        <p>No popular books data available yet.</p>
                        <p>Check back after more borrowing activity!</p>
                        <a href="book_reservation.php" class="make-reservation-btn">Browse All Books</a>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach($popular_books as $book): ?>
                            <div class="book-card">
                                <div class="book-cover" style="background-image: url('<?php echo htmlspecialchars($book['cover_image']); ?>')"></div>
                                <div class="popularity-badge">
                                    <i class="fas fa-fire"></i> Popular
                                </div>
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p><?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="category-badge"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></p>
                                <button class="reserve-btn" data-book-id="<?php echo $book['book_id']; ?>">Reserve Now</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Recommended Books Section -->
            <section class="featured-books recommended-books">
                <h2><i class="fas fa-heart"></i> Recommended For You</h2>
                <?php if ($recommendation_source === 'history'): ?>
                    <p>Books we think you'll love based on your reading history</p>
                <?php else: ?>
                    <p>Books we think you'll love based on your preferences</p>
                <?php endif; ?>
                <?php if (empty($recommended_books)): ?>
                    <div class="no-reservations">
                        <i class="fas fa-heart fa-3x"></i>
                        <?php if (empty($user_preferences) && empty($history_categories)): ?>
                            <p>Select your favorite categories above to get personalized recommendations!</p>
                            <p>We'll find books that match your interests.</p>
                        <?php else: ?>
                            <p>We don't have book recommendations for you yet.</p>
                            <p>Check back soon or explore our collection!</p>
                        <?php endif; ?>
                        <a href="book_reservation.php" class="make-reservation-btn">Browse All Books</a>
                    </div>
                <?php else: ?>
                    <div class="books-grid">
                        <?php foreach($recommended_books as $book): ?>
                            <div class="book-card">
                                <div class="book-cover" style="background-image: url('<?php echo htmlspecialchars($book['cover_image']); ?>')"></div>
                                <div class="recommended-badge">
                                    <i class="fas fa-thumbs-up"></i> For You
                                </div>
                                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p><?php echo htmlspecialchars($book['author']); ?></p>
                                <p class="category-badge"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></p>
                                <button class="reserve-btn" data-book-id="<?php echo $book['book_id']; ?>">Reserve Now</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Library System. All rights reserved.</p>
        </footer>
    </div>

    <script src="homepage.js"></script>
</body>
</html>
