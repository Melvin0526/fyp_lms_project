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


// For demo purposes, let's assume we have these stats
$total_books = 5280;
$borrowed_books = 578;
$overdue_books = 42;


// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Library System</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Library Admin</h2>
            </div>            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="admin_homepage.php">
                            <span class="icon dashboard-icon"></span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="admin_user_management.php">
                            <span class="icon users-icon"></span>
                            User Management
                        </a>
                    </li>
                    <li>
                        <a href="admin_book_management.php">
                            <span class="icon books-icon"></span>
                            Book Management
                        </a>
                    </li>
                    <li>
                        <a href="admin_requests.php">
                            <span class="icon borrowing-icon"></span>
                            Requests
                        </a>
                    </li>
                    <li>
                        <a href="admin_room_management.php">
                            <span class="icon reports-icon"></span>
                            Room Management
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span class="icon settings-icon"></span>
                            System Settings
                        </a>
                    </li>
                </ul>            </nav>            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon logout-icon"></span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-search">
                    <span class="search-icon"></span>
                    <input type="text" placeholder="Search..." />
                </div>
                <div class="header-user">
                    <span class="notification-bell">
                        <span class="notification-count">3</span>
                    </span>                <div class="user-profile">
                    <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                    <span class="user-role">Administrator</span>
                    <a href="homepage.php" class="switch-view">Switch to User View</a>
                </div>
                </div>
            </header>            
            <div class="dashboard">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-description">Welcome back, <?php echo htmlspecialchars($admin_username); ?>! Here's an overview of the system.</p>
                
               
            </div>
        </main>
    </div>

    <script src="admin.js"></script>
</body>
</html>
