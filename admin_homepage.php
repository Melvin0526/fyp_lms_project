<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection file
include 'config.php';

// Get admin information from session
$admin_username = $_SESSION['username'];

// Fetch library statistics
$stats_query = "SELECT 
                (SELECT COUNT(*) FROM books) as total_books,
                (SELECT COUNT(*) FROM book_loans WHERE status = 'borrowed') as borrowed_books,
                (SELECT COUNT(*) FROM book_loans WHERE status = 'borrowed' AND due_date < NOW()) as overdue_books";

$stats_result = $conn->query($stats_query);
if ($stats_result) {
    $stats = $stats_result->fetch_assoc();
    $total_books = $stats['total_books'];
    $borrowed_books = $stats['borrowed_books'];
    $overdue_books = $stats['overdue_books'];
} else {
    // Default values if query fails
    $total_books = 0;
    $borrowed_books = 0;
    $overdue_books = 0;
}

// Process physical book checkout
$checkout_message = '';
$checkout_status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_submit'])) {
    // Get form data
    $user_identifier = trim($_POST['user_identifier']);
    $book_identifier = trim($_POST['book_identifier']);
    
    // Fixed 14 days borrowing period
    $due_days = 14;
    
    // Validate inputs
    if (empty($user_identifier) || empty($book_identifier)) {
        $checkout_message = 'Please fill in all required fields.';
        $checkout_status = 'error';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // First, find the user by email or username
            $user_query = "SELECT id, username, email FROM users WHERE email = ? OR username = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param('ss', $user_identifier, $user_identifier);
            $stmt->execute();
            $user_result = $stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                throw new Exception('User not found with the provided email or username.');
            }
            
            $user = $user_result->fetch_assoc();
            $user_id = $user['id'];
            
            // Next, find the book by ISBN, title, or ID
            $book_query = "SELECT book_id, title, available_copies FROM books WHERE book_id = ? OR isbn = ? OR title LIKE ?";
            $book_title_search = "%{$book_identifier}%";
            $stmt = $conn->prepare($book_query);
            $stmt->bind_param('iss', $book_identifier, $book_identifier, $book_title_search);
            $stmt->execute();
            $book_result = $stmt->get_result();
            
            if ($book_result->num_rows === 0) {
                throw new Exception('Book not found with the provided ID, ISBN, or title.');
            }
            
            // Use the first book if multiple matches (for title search)
            $book = $book_result->fetch_assoc();
            $book_id = $book['book_id'];
            
            // Check if book is available
            if ($book['available_copies'] <= 0) {
                throw new Exception('This book is currently not available for checkout.');
            }
            
            // Check if the user already has this book borrowed
            $check_query = "SELECT loan_id FROM book_loans 
                           WHERE id = ? AND book_id = ? AND status IN ('reserved', 'ready_for_pickup', 'borrowed')";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('ii', $user_id, $book_id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                throw new Exception('This user already has this book borrowed or reserved.');
            }
            
            // Check borrowing limit (max 3 active loans per user)
            $limit_query = "SELECT COUNT(*) as active_loans FROM book_loans 
                           WHERE id = ? AND status IN ('reserved', 'ready_for_pickup', 'borrowed')";
            $stmt = $conn->prepare($limit_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $limit_result = $stmt->get_result()->fetch_assoc();
            
            if ($limit_result['active_loans'] >= 3) {
                throw new Exception('User has reached the maximum borrowing limit (3 books).');
            }
            
            // Calculate due date - fixed 14 days
            $current_date = date('Y-m-d H:i:s');
            $due_date = date('Y-m-d', strtotime("+{$due_days} days"));
            
            // Create a new loan record
            // Using picked_up_at instead of borrowed_at since that's the available column
            $loan_query = "INSERT INTO book_loans (id, book_id, reserved_at, picked_up_at, due_date, status) 
                          VALUES (?, ?, ?, ?, ?, 'borrowed')";
            $stmt = $conn->prepare($loan_query);
            $stmt->bind_param('iisss', $user_id, $book_id, $current_date, $current_date, $due_date);
            $stmt->execute();
            
            if ($stmt->affected_rows <= 0) {
                throw new Exception('Failed to create loan record.');
            }
            
            // Update book available copies
            $update_query = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('i', $book_id);
            $stmt->execute();
            
            if ($stmt->affected_rows <= 0) {
                throw new Exception('Failed to update book availability.');
            }
            
            // Commit transaction
            $conn->commit();
            
            $checkout_message = "Book \"{$book['title']}\" has been successfully checked out to {$user['username']} until " . date('F j, Y', strtotime($due_date)) . " (14 days).";
            $checkout_status = 'success';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $checkout_message = 'Error: ' . $e->getMessage();
            $checkout_status = 'error';
        }
    }
}

// Fetch data for charts

// 1. Daily borrowing activity for the past 14 days (replacing monthly)
$activity_query = "SELECT 
                    DATE_FORMAT(reserved_at, '%b %d') as day,
                    COUNT(*) as total_loans
                  FROM book_loans
                  WHERE reserved_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY)
                  GROUP BY DATE(reserved_at)
                  ORDER BY DATE(reserved_at)";
$activity_result = $conn->query($activity_query);
$activity_data = [];

if ($activity_result) {
    while ($row = $activity_result->fetch_assoc()) {
        $activity_data[$row['day']] = (int)$row['total_loans'];
    }
}

// If no data or limited data, add some sample data for demo purposes
/*if (count($activity_data) < 3) {
    // Get the current date and create some sample data for the past 14 days
    $current_date = new DateTime();
    for ($i = 14; $i >= 0; $i--) {
        $date = clone $current_date;
        $date->modify("-$i days");
        $date_str = $date->format('M d');
        
        // Only add sample data if there's no real data for this date
        if (!isset($activity_data[$date_str])) {
            // Generate a random number between 0 and 8 with more weight to 1-4 range
            $activity_data[$date_str] = rand(0, 10) > 7 ? rand(5, 8) : rand(1, 4);
        }
    }
    // Sort by date
    ksort($activity_data);
}
*/

// 2. Book categories distribution
$categories_query = "SELECT 
                      c.name, 
                      COUNT(b.book_id) as book_count 
                    FROM categories c
                    LEFT JOIN books b ON c.category_id = b.category_id 
                    WHERE b.book_id IS NOT NULL
                    GROUP BY c.name
                    ORDER BY book_count DESC
                    LIMIT 8";
$categories_result = $conn->query($categories_query);
$categories_data = [];

if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories_data[$row['name']] = (int)$row['book_count'];
    }
}

// 3. Book status distribution
$status_query = "SELECT 
                  CASE 
                    WHEN status = 'available' AND available_copies > 0 THEN 'Available'
                    WHEN status = 'unavailable' OR available_copies = 0 THEN 'Unavailable'
                  END as display_status,
                  COUNT(*) as count
                FROM books
                GROUP BY display_status";
$status_result = $conn->query($status_query);
$status_data = [];

if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        if ($row['display_status'] !== NULL) {
            $status_data[$row['display_status']] = (int)$row['count'];
        }
    }
}

// 4. Loan status distribution
$loan_status_query = "SELECT 
                        status,
                        COUNT(*) as count
                      FROM book_loans
                      WHERE status IN ('reserved', 'ready_for_pickup', 'borrowed', 'returned', 'overdue')
                      GROUP BY status";
$loan_status_result = $conn->query($loan_status_query);
$loan_status_data = [];

if ($loan_status_result) {
    while ($row = $loan_status_result->fetch_assoc()) {
        // Convert status to title case for better display
        $status = ucfirst(str_replace('_', ' ', $row['status']));
        $loan_status_data[$status] = (int)$row['count'];
    }
}

// 5. Most borrowed categories
$borrowed_categories_query = "SELECT 
                             c.name as category_name, 
                             COUNT(l.loan_id) as borrow_count
                           FROM book_loans l
                           JOIN books b ON l.book_id = b.book_id
                           JOIN categories c ON b.category_id = c.category_id
                           WHERE l.status IN ('borrowed', 'returned')
                           GROUP BY c.category_id
                           ORDER BY borrow_count DESC
                           LIMIT 6";
$borrowed_categories_result = $conn->query($borrowed_categories_query);
$borrowed_categories_data = [];

if ($borrowed_categories_result) {
    while ($row = $borrowed_categories_result->fetch_assoc()) {
        $borrowed_categories_data[$row['category_name']] = (int)$row['borrow_count'];
    }
}

// 6. Most borrowed books
$borrowed_books_query = "SELECT 
                          b.title,
                          b.author,
                          COUNT(l.loan_id) as borrow_count
                        FROM book_loans l
                        JOIN books b ON l.book_id = b.book_id
                        WHERE l.status IN ('borrowed', 'returned')
                        GROUP BY b.book_id
                        ORDER BY borrow_count DESC
                        LIMIT 10";
$borrowed_books_result = $conn->query($borrowed_books_query);
$borrowed_books_data = [];
$borrowed_books_authors = [];

if ($borrowed_books_result) {
    while ($row = $borrowed_books_result->fetch_assoc()) {
        // Truncate long titles for better display
        $title = (strlen($row['title']) > 25) ? substr($row['title'], 0, 22) . '...' : $row['title'];
        $borrowed_books_data[$title] = (int)$row['borrow_count'];
        $borrowed_books_authors[$title] = $row['author'];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Library System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="admin_charts.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Library Admin</h2>
            </div>            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="admin_homepage.php">
                            <span class="icon"><i class="fas fa-home"></i></span>
                            <span class="text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_user_management.php">
                            <span class="icon"><i class="fas fa-users"></i></span>
                            <span class="text">User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_book_management.php">
                            <span class="icon"><i class="fas fa-book"></i></span>
                            <span class="text">Book Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_requests.php">
                            <span class="icon"><i class="fas fa-exchange-alt"></i></span>
                            <span class="text">Book Reservation</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_room_management.php">
                            <span class="icon"><i class="fas fa-door-open"></i></span>
                            <span class="text">Room Management</span>
                        </a>
                    </li>
                </ul>
            </nav>            
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div style="flex: 1;"></div> <!-- Empty div to push content to right -->
                <div class="header-user">
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
            </header>  
                   
            <div class="dashboard">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-description">Welcome back, <?php echo htmlspecialchars($admin_username); ?>! Here's an overview of the system.</p>
                
                <!-- Statistics Cards -->
                <div class="stat-cards">
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div class="stat-icon books-stat">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-data">
                                <h3>Total Books</h3>
                                <div class="stat-number"><?php echo number_format($total_books); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div class="stat-icon borrowed-stat">
                                <i class="fas fa-book-reader"></i>
                            </div>
                            <div class="stat-data">
                                <h3>Books Borrowed</h3>
                                <div class="stat-number"><?php echo number_format($borrowed_books); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-card-inner">
                            <div class="stat-icon overdue-stat">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="stat-data">
                                <h3>Overdue Books</h3>
                                <div class="stat-number"><?php echo number_format($overdue_books); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Physical Book Checkout Section -->
                <section class="checkout-section">
                    <h2><i class="fas fa-exchange-alt"></i> Physical Book Checkout</h2>
                    <p>Process borrowing for patrons who visit the library in person. All books are loaned for 14 days.</p>
                    
                    <div class="checkout-form">
                        <form method="POST" action="">
                            <div class="form-row">
                                <label for="user_identifier">User Email or Username:<span class="required">*</span></label>
                                <input type="text" id="user_identifier" name="user_identifier" placeholder="Enter user email or username" required>
                                <div id="user-suggestions" class="autocomplete-suggestions"></div>
                            </div>
                            
                            <div class="form-row">
                                <label for="book_identifier">Book ID, ISBN, or Title:<span class="required">*</span></label>
                                <input type="text" id="book_identifier" name="book_identifier" placeholder="Enter book ID, ISBN, or title" required>
                                <div id="book-suggestions" class="autocomplete-suggestions"></div>
                            </div>
                            
                            <div class="form-row borrowing-period">
                                <div class="period-info">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Borrowing Period: <strong>14 days</strong></span>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <button type="submit" name="checkout_submit" class="checkout-btn">
                                    <i class="fas fa-check-circle"></i> Process Checkout
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!empty($checkout_message)): ?>
                            <div class="checkout-message <?php echo $checkout_status; ?>">
                                <?php echo $checkout_message; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="checkout-info">
                        <h3>Checkout Guidelines</h3>
                        <ul>
                            <li><i class="fas fa-check"></i> Users can borrow up to 3 books at a time</li>
                            <li><i class="fas fa-check"></i> All books must be returned within 14 days</li>
                            <li><i class="fas fa-check"></i> Late returns incur a fine of $0.50 per day</li>
                            <li><i class="fas fa-check"></i> Damaged or lost books must be replaced or paid for</li>
                        </ul>
                    </div>
                </section>

                <!-- Dashboard Charts Section -->
                <div class="dashboard-charts">
                    <!-- Activity Overview -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-line"></i> Monthly Borrowing Activity</h2>
                        </div>
                        <div class="chart-body">
                            <canvas id="activity-chart"></canvas>
                        </div>
                    </div>

                    <!-- Book Categories Distribution -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-pie"></i> Book Categories</h2>
                            <div class="section-actions">
                                <select id="category-chart-type" class="chart-type-selector">
                                    <option value="pie" selected>Pie Chart</option>
                                    <option value="doughnut">Doughnut Chart</option>
                                    <option value="bar">Bar Chart</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-body">
                            <canvas id="categories-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Book Status Distribution -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-bar"></i> Book Status</h2>
                        </div>
                        <div class="chart-body">
                            <canvas id="status-chart"></canvas>
                        </div>
                    </div>
                    
                    <!-- Loan Status Distribution -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-tasks"></i> Loan Status Distribution</h2>
                        </div>
                        <div class="chart-body">
                            <canvas id="loan-status-chart"></canvas>
                        </div>
                    </div>

                    <!-- Most Borrowed Categories Chart -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-chart-bar"></i> Most Borrowed Categories</h2>
                        </div>
                        <div class="chart-body">
                            <canvas id="borrowed-categories-chart"></canvas>
                        </div>
                    </div>

                    <!-- Most Borrowed Books Chart -->
                    <div class="chart-container">
                        <div class="section-header">
                            <h2><i class="fas fa-book"></i> Most Borrowed Books</h2>
                        </div>
                        <div class="chart-body">
                            <canvas id="borrowed-books-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart Data -->
    <script type="application/json" id="activity-data">
        <?php echo json_encode($activity_data); ?>
    </script>
    <script type="application/json" id="categories-data">
        <?php echo json_encode($categories_data); ?>
    </script>
    <script type="application/json" id="status-data">
        <?php echo json_encode($status_data); ?>
    </script>
    <script type="application/json" id="loan-status-data">
        <?php echo json_encode($loan_status_data); ?>
    </script>

    <script type="application/json" id="borrowed-categories-data">
        <?php echo json_encode($borrowed_categories_data); ?>
    </script>
    <script type="application/json" id="borrowed-books-data">
        <?php echo json_encode($borrowed_books_data); ?>
    </script>
    <script type="application/json" id="borrowed-books-authors">
        <?php echo json_encode($borrowed_books_authors); ?>
    </script>

    <script src="admin.js"></script>
    <script src="admin_checkout.js"></script>
    <script src="admin_charts.js"></script>
</body>
</html>
