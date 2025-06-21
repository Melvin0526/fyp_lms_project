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

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve & Pickup Requests | Library Admin</title>    
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_requests.css">
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
                    <li>
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
                        <li class="active">
                        <a href="admin_requests.php">
                            <span class="icon"><i class="fas fa-exchange-alt"></i></span>
                            <span class="text">Book Reservation</span>
                        </a>
                    </li>
                        <a href="admin_room_management.php">
                            <span class="icon"><i class="fas fa-door-open"></i></span>
                            <span class="text">Room Management</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon logout-icon"></span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-user">
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                </div>
            </header>

            <div class="dashboard">
                <h1 class="page-title">Reserve & Pickup Requests</h1>
                <p class="page-description">Manage book reservation and pickup requests from library users.</p>
                
                <!-- Tab Navigation -->
                <div class="tab-container">
                    <div class="tabs">
                        <div class="tab active" data-tab="all-requests">All Requests</div>
                        <div class="tab" data-tab="reserve-requests">Reservations</div>
                        <div class="tab" data-tab="pickup-requests">Pickups</div>
                    </div>
                    
                    <!-- All Requests Tab -->
                    <div class="tab-content active" id="all-requests">
                        <div class="request-filters">
                            <div class="filter-group">
                                <select class="filter-select" id="status-filter">
                                    <option value="">All Status</option>
                                    <option value="reserved">Pending</option>
                                    <option value="ready_for_pickup">Ready</option>
                                    <option value="borrowed">Borrowed</option>
                                    <option value="returned">Returned</option>
                                    <option value="returned_late">Returned Late</option>
                                    <option value="cancelled">Cancelled</option>
                                    <option value="expired">Expired</option>
                                </select>
                                
                                <select class="filter-select" id="type-filter">
                                    <option value="">All Types</option>
                                    <option value="reserve">Reservations</option>
                                    <option value="pickup">Pickups</option>
                                </select>
                                
                                <input type="date" class="date-picker" id="date-filter" placeholder="Filter by date">
                            </div>
                        </div>
                        
                        <!-- The table will be loaded dynamically via JavaScript -->
                        <div class="loading-container">
                            <div class="loading-spinner"></div>
                            <p>Loading requests...</p>
                        </div>
                    </div>
                    
                    <!-- Reserve Requests Tab -->
                    <div class="tab-content" id="reserve-requests">
                        <!-- Will be populated using JavaScript -->
                    </div>
                    
                    <!-- Pickup Requests Tab -->
                    <div class="tab-content" id="pickup-requests">
                        <!-- Will be populated using JavaScript -->
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Notification Toast -->
    <div id="notification-toast" class="notification-toast"></div>
    
    <script src="admin_requests.js"></script>
</body>
</html>
