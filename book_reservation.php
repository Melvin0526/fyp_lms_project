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
    <title>Reserve and Pick Up | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="book_reservation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Library System (LMS)</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="book_reservation.php" class="active">Browse Book</a></li>
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
                        <a href="change_password.php">Change Password</a>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="admin_homepage.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main>            <section class="page-header">
                <h2>Book Reservation</h2>
                <p>Search, reserve, and manage your book pickups</p>
                
                <div class="search-container">
                    <div class="search-box">
                        <input type="text" id="book-search" placeholder="Search by title, author, or ISBN...">
                        <button id="search-btn" class="search-btn">Search</button>
                    </div>
                    <div class="filter-options">
                        <select id="category-filter">
                            <option value="">All Categories</option>
                            <option value="fiction">Fiction</option>
                            <option value="non-fiction">Non-Fiction</option>
                            <option value="sci-fi">Science Fiction</option>
                            <option value="mystery">Mystery</option>
                            <option value="biography">Biography</option>
                            <option value="history">History</option>
                            <option value="business">Business</option>
                            <option value="science">Science</option>
                        </select>
                        <select id="availability-filter">
                            <option value="">All Availability</option>
                            <option value="available">Available Now</option>
                            <option value="reserved">Reserved</option>
                            <option value="borrowed">Borrowed</option>
                        </select>
                    </div>
                </div>
            </section>

        </main>
    </div>

    <script src="book_reservation.js"></script>
</body>
</html>
