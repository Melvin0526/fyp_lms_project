<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrowing History | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="borrow_history.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Library System (LMS) </h1>
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="book_reservation.php">Browse Book</a></li>
                    <li><a href="borrow_history.php" class="active">Borrow History</a></li>
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
            <div class="page-header">
                <h2>My Books</h2>
                <p>View your current reservations and borrowing history</p>
            </div>
            
            <div class="tabs-container">
                <div class="tab-header">
                    <button class="tab-btn active" data-tab="current-tab">Current Reservations</button>
                    <button class="tab-btn" data-tab="past-tab">Past Borrowings</button>
                </div>
                
                <div id="current-tab" class="tab-pane active">
                    <!-- Content will be loaded dynamically -->
                </div>
                
                <div id="past-tab" class="tab-pane">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </main>
    </div>
    
    <!-- Confirmation Modal for Cancellation -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <!-- Content will be dynamically updated -->
        </div>
    </div>
    
    <!-- Notification Toast -->
    <div id="notification-toast" class="notification-toast">
        <div class="toast-content">
            <span class="toast-message"></span>
            <button class="toast-close">&times;</button>
        </div>
    </div>

    <script src="borrow_history.js"></script>
</body>
</html>
