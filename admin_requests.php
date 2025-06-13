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

// For demo purposes, let's assume we have some requests data
$requests = [
    [
        'id' => 1,
        'type' => 'reserve',
        'book_title' => 'The Great Gatsby',
        'book_id' => 1,
        'user_name' => 'John Doe',
        'user_id' => 5,
        'request_date' => '2025-05-28',
        'status' => 'pending',
        'pickup_date' => '2025-06-07'
    ],
    [
        'id' => 2,
        'type' => 'pickup',
        'book_title' => 'To Kill a Mockingbird',
        'book_id' => 2,
        'user_name' => 'Jane Doe',
        'user_id' => 8,
        'request_date' => '2025-05-29',
        'status' => 'ready',
        'pickup_date' => '2025-06-02'
    ],
    [
        'id' => 3,
        'type' => 'reserve',
        'book_title' => 'Introduction to Algorithms',
        'book_id' => 4,
        'user_name' => 'Sam Smith',
        'user_id' => 12,
        'request_date' => '2025-05-30',
        'status' => 'approved',
        'pickup_date' => '2025-06-10'
    ],
    [
        'id' => 4,
        'type' => 'pickup',
        'book_title' => '1984',
        'book_id' => 5,
        'user_name' => 'Emily Jones',
        'user_id' => 15,
        'request_date' => '2025-06-01',
        'status' => 'completed',
        'pickup_date' => '2025-06-01'
    ],
    [
        'id' => 5,
        'type' => 'reserve',
        'book_title' => 'The Hobbit',
        'book_id' => 6,
        'user_name' => 'Michael W.',
        'user_id' => 18,
        'request_date' => '2025-06-02',
        'status' => 'canceled',
        'pickup_date' => '2025-06-12'
    ],
    [
        'id' => 6,
        'type' => 'pickup',
        'book_title' => 'Principles of Physics',
        'book_id' => 3,
        'user_name' => 'Sarah C.',
        'user_id' => 21,
        'request_date' => '2025-06-03',
        'status' => 'pending',
        'pickup_date' => '2025-06-08'
    ],
    [
        'id' => 7,
        'type' => 'reserve',
        'book_title' => 'To Kill a Mockingbird',
        'book_id' => 2,
        'user_name' => 'Robert J.',
        'user_id' => 24,
        'request_date' => '2025-06-04',
        'status' => 'pending',
        'pickup_date' => '2025-06-14'
    ],
];

// Close the database connection (in a real application)
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve & Pickup Requests | Library Admin</title>    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_requests.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <li class="active">
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
                    <input type="text" placeholder="Search requests..." />
                </div>
                <div class="header-user">
                    <span class="notification-bell">
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
                <h1 class="page-title">Reserve & Pickup Requests</h1>
                <p class="page-description">Manage book reservation and pickup requests from library users.</p>
                
                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card pending-stat">
                        <div class="stat-number">14</div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-card approved-stat">
                        <div class="stat-number">8</div>
                        <div class="stat-label">Approved Reservations</div>
                    </div>
                    <div class="stat-card ready-stat">
                        <div class="stat-number">5</div>
                        <div class="stat-label">Ready for Pickup</div>
                    </div>
                    <div class="stat-card completed-stat">
                        <div class="stat-number">126</div>
                        <div class="stat-label">Completed This Month</div>
                    </div>
                </div>
                
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
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="ready">Ready</option>
                                    <option value="completed">Completed</option>
                                    <option value="canceled">Canceled</option>
                                </select>
                                
                                <select class="filter-select" id="type-filter">
                                    <option value="">All Types</option>
                                    <option value="reserve">Reservations</option>
                                    <option value="pickup">Pickups</option>
                                </select>
                                
                                <input type="date" class="date-picker" placeholder="Filter by date">
                            </div>
                        </div>
                        
                        <table class="request-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Type</th>
                                    <th>Book Title</th>
                                    <th>User</th>
                                    <th>Request Date</th>
                                    <th>Pickup Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['id']; ?></td>
                                        <td>
                                            <span class="type-badge type-<?php echo $request['type']; ?>">
                                                <?php echo ucfirst($request['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['book_title']); ?></td>
                                        <td><?php echo htmlspecialchars($request['user_name']); ?></td>
                                        <td><?php echo $request['request_date']; ?></td>
                                        <td><?php echo $request['pickup_date']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="request-actions">
                                                <?php if ($request['status'] === 'pending' && $request['type'] === 'reserve'): ?>
                                                    <button class="action-btn approve-btn" data-id="<?php echo $request['id']; ?>">Approve</button>
                                                    <button class="action-btn reject-btn" data-id="<?php echo $request['id']; ?>">Reject</button>
                                                <?php elseif ($request['status'] === 'approved'): ?>
                                                    <button class="action-btn mark-ready-btn" data-id="<?php echo $request['id']; ?>">Mark Ready</button>
                                                <?php elseif ($request['status'] === 'ready' || ($request['status'] === 'pending' && $request['type'] === 'pickup')): ?>
                                                    <button class="action-btn complete-btn" data-id="<?php echo $request['id']; ?>">Complete</button>
                                                <?php endif; ?>
                                                <button class="action-btn view-btn" data-id="<?php echo $request['id']; ?>">View</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="pagination">
                            <button class="page-btn">&laquo;</button>
                            <button class="page-btn active">1</button>
                            <button class="page-btn">2</button>
                            <button class="page-btn">3</button>
                            <button class="page-btn">&raquo;</button>
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
    </div>    <script src="admin.js"></script>
    <script src="admin_requests.js"></script>
</body>
</html>
