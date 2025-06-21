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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Library System</title>
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
                <div class="header-search">
                    <span class="search-icon"><i class="fas fa-search"></i></span>
                    <input type="text" placeholder="Search..." />
                </div>
                <div class="header-user">
                    <span class="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count">3</span>
                    </span>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                        <span class="user-role">Administrator</span>
                        <a href="homepage.php" class="switch-view">Switch to User View</a>
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
            </div>
        </main>
    </div>

    <script src="admin.js"></script>
    <script src="admin_checkout.js"></script>
</body>
</html>
