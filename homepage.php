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

// Get user's room reservations (limit to 5 most recent)
$user_reservations = getUserReservations($conn, $user_id, 5);
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
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="admin_homepage.php">Admin Dashboard</a>
                        <?php endif; ?>
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

            <section class="featured-books">
                <h2>Featured Books</h2>
                <div class="books-grid">
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>To Kill a Mockingbird</h3>
                        <p>Harper Lee</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>1984</h3>
                        <p>George Orwell</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>The Great Gatsby</h3>
                        <p>F. Scott Fitzgerald</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>Pride and Prejudice</h3>
                        <p>Jane Austen</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                </div>
            </section>            
            
            <section class="featured-books">
                <h2>Recommendation Books</h2>
                <div class="books-grid">
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>To Kill a Mockingbird</h3>
                        <p>Harper Lee</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>1984</h3>
                        <p>George Orwell</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>The Great Gatsby</h3>
                        <p>F. Scott Fitzgerald</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                    <div class="book-card">
                        <div class="book-cover"></div>
                        <h3>Pride and Prejudice</h3>
                        <p>Jane Austen</p>
                        <button class="borrow-btn">Borrow Now</button>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Library System. All rights reserved.</p>
        </footer>
    </div>

    <script src="homepage.js"></script>
</body>
</html>
