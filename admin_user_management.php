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

// For demo purposes, let's assume we have some user data
$users = [
    ['id' => 1, 'username' => 'johndoe', 'email' => 'john.doe@example.com', 'status' => 'Active', 'role' => 'Student', 'created' => '2025-01-15'],
    ['id' => 2, 'username' => 'janedoe', 'email' => 'jane.doe@example.com', 'status' => 'Active', 'role' => 'Faculty', 'created' => '2025-01-18'],
    ['id' => 3, 'username' => 'samsmith', 'email' => 'sam.smith@example.com', 'status' => 'Suspended', 'role' => 'Student', 'created' => '2025-02-03'],
    ['id' => 4, 'username' => 'emilyjones', 'email' => 'emily.jones@example.com', 'status' => 'Active', 'role' => 'Staff', 'created' => '2025-02-10'],
    ['id' => 5, 'username' => 'michaelw', 'email' => 'michael.w@example.com', 'status' => 'Inactive', 'role' => 'Student', 'created' => '2025-02-22'],
    ['id' => 6, 'username' => 'sarahc', 'email' => 'sarah.c@example.com', 'status' => 'Active', 'role' => 'Student', 'created' => '2025-03-05'],
    ['id' => 7, 'username' => 'robertj', 'email' => 'robert.j@example.com', 'status' => 'Active', 'role' => 'Faculty', 'created' => '2025-03-12'],
    ['id' => 8, 'username' => 'lisap', 'email' => 'lisa.p@example.com', 'status' => 'Active', 'role' => 'Student', 'created' => '2025-03-20']
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
    <title>User Management | Library Admin</title>    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_user_management.css">
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
                    <li class="active">
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
                <div class="header-search">
                    <span class="search-icon"></span>
                    <input type="text" placeholder="Search users..." />
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
                <h1 class="page-title">User Management</h1>
                <p class="page-description">Manage library system users, their roles, and permissions.</p>
                
                <div class="user-filters">
                    <div class="filter-group">
                        <select class="filter-select">
                            <option value="">All Roles</option>
                            <option value="student">Student</option>
                            <option value="faculty">Faculty</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                        
                        <select class="filter-select">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                        
                        <select class="filter-select">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="a-z">A-Z</option>
                            <option value="z-a">Z-A</option>
                        </select>
                    </div>
                    
                    <button class="user-create-btn">
                        <span class="plus-icon"></span>
                        Add New User
                    </button>
                </div>
                
                <table class="user-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($user['status']); ?>">
                                        <?php echo htmlspecialchars($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="role-badge">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['created']; ?></td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-btn view-details-btn" title="View Details">
                                            <i>👁️</i>
                                        </button>
                                        <button class="action-btn edit-btn" title="Edit User">
                                            <i>✏️</i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Delete User">
                                            <i>🗑️</i>
                                        </button>
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
                    <button class="page-btn">4</button>
                    <button class="page-btn">5</button>
                    <button class="page-btn">&raquo;</button>
                </div>
            </div>
        </main>
    </div>    <script src="admin.js"></script>
    <script src="admin_user_management.js"></script>
</body>
</html>
