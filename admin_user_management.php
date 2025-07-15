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

// Set default pagination values
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Initialize filters
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the query with filters
$sql = "SELECT id, username, email, usertype, status, phone FROM users WHERE 1=1";

// Apply filters
if (!empty($role_filter)) {
    $sql .= " AND usertype = '" . $conn->real_escape_string($role_filter) . "'";
}

if (!empty($status_filter)) {
    $sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}

// Apply sorting
switch ($sort) {
    case 'oldest':
        $sql .= " ORDER BY id ASC";
        break;
    case 'a-z':
        $sql .= " ORDER BY username ASC";
        break;
    case 'z-a':
        $sql .= " ORDER BY username DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY id DESC";
        break;
}

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM users WHERE 1=1";

if (!empty($role_filter)) {
    $count_sql .= " AND usertype = '" . $conn->real_escape_string($role_filter) . "'";
}

if (!empty($status_filter)) {
    $count_sql .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
}

$count_result = $conn->query($count_sql);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Add pagination limit
$sql .= " LIMIT $offset, $records_per_page";

// Execute query
$result = $conn->query($sql);

// Fetch users
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get unique user roles for the filter dropdown
$roles_sql = "SELECT DISTINCT usertype FROM users ORDER BY usertype";
$roles_result = $conn->query($roles_sql);
$user_roles = [];
if ($roles_result && $roles_result->num_rows > 0) {
    while ($role = $roles_result->fetch_assoc()) {
        $user_roles[] = $role['usertype'];
    }
}

// Get unique user statuses for the filter dropdown
$statuses_sql = "SELECT DISTINCT status FROM users ORDER BY status";
$statuses_result = $conn->query($statuses_sql);
$user_statuses = [];
if ($statuses_result && $statuses_result->num_rows > 0) {
    while ($status = $statuses_result->fetch_assoc()) {
        $user_statuses[] = $status['status'];
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Library Admin</title>    
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_user_management.css">
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
                    <li class="active">
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
                    <span class="icon logout-icon">
                    </span>
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
                <h1 class="page-title">User Management</h1>
                <p class="page-description">Manage library system users, their roles, and permissions.</p>
                
                <!-- Success and error messages -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="success-message">
                        <?php 
                        switch($_GET['success']) {
                            case 'user_created':
                                echo 'User has been successfully created.';
                                break;
                            case 'user_updated':
                                echo 'User information has been successfully updated.';
                                break;
                            case 'user_deleted':
                                echo 'User has been successfully deleted.';
                                break;
                            default:
                                echo 'Operation completed successfully.';
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="error-message">
                        <?php 
                        switch($_GET['error']) {
                            case 'create_failed':
                                echo 'Failed to create user. Please try again.';
                                if(isset($_GET['msg'])) {
                                    echo '<br><small>Error: ' . htmlspecialchars($_GET['msg']) . '</small>';
                                }
                                break;
                            case 'update_failed':
                                echo 'Failed to update user information. Please try again.';
                                if(isset($_GET['msg'])) {
                                    echo '<br><small>Error: ' . htmlspecialchars($_GET['msg']) . '</small>';
                                }
                                break;
                            case 'delete_failed':
                                echo 'Failed to delete user. Please try again.';
                                if(isset($_GET['msg'])) {
                                    echo '<br><small>Error: ' . htmlspecialchars($_GET['msg']) . '</small>';
                                }
                                break;
                            case 'user_exists':
                                echo 'Username or email already exists.';
                                break;
                            case 'email_exists':
                                echo 'Email already in use by another user.';
                                break;
                            case 'username_exists':
                                echo 'Username already in use by another user.';
                                break;
                            case 'cannot_delete_self':
                                echo 'You cannot delete your own account.';
                                break;
                            default:
                                echo 'An error occurred. Please try again.';
                                if(isset($_GET['msg'])) {
                                    echo '<br><small>Error: ' . htmlspecialchars($_GET['msg']) . '</small>';
                                }
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="user-filters">
                    <div class="filter-group">
                        <form id="filter-form" method="get" action="">
                            <select name="role" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <?php foreach($user_roles as $role): ?>
                                    <option value="<?php echo htmlspecialchars($role); ?>" <?php if($role_filter === $role) echo "selected"; ?>>
                                        <?php echo htmlspecialchars(ucfirst($role)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <?php foreach($user_statuses as $status): ?>
                                    <option value="<?php echo htmlspecialchars($status); ?>" <?php if($status_filter === $status) echo "selected"; ?>>
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="newest" <?php if($sort === 'newest') echo "selected"; ?>>Newest First</option>
                                <option value="oldest" <?php if($sort === 'oldest') echo "selected"; ?>>Oldest First</option>
                                <option value="a-z" <?php if($sort === 'a-z') echo "selected"; ?>>A-Z</option>
                                <option value="z-a" <?php if($sort === 'z-a') echo "selected"; ?>>Z-A</option>
                            </select>
                        </form>
                    </div>
                    
                    <button class="user-create-btn" id="add-user-btn">
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
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($user['status']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="role-badge">
                                            <?php echo htmlspecialchars(ucfirst($user['usertype'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <button class="action-btn view-details-btn" 
                                                    data-user-id="<?php echo $user['id']; ?>" 
                                                    title="View Details">
                                                View
                                            </button>
                                            <button class="action-btn edit-btn" 
                                                    data-user-id="<?php echo $user['id']; ?>" 
                                                    data-user-name="<?php echo htmlspecialchars($user['username']); ?>"
                                                    data-user-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                    data-user-phone="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                                    data-user-status="<?php echo htmlspecialchars($user['status']); ?>"
                                                    data-user-role="<?php echo htmlspecialchars($user['usertype']); ?>"
                                                    title="Edit User">
                                                Edit
                                            </button>
                                            <button class="action-btn delete-btn" 
                                                    data-user-id="<?php echo $user['id']; ?>" 
                                                    data-user-name="<?php echo htmlspecialchars($user['username']); ?>"
                                                    title="Delete User">
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 20px;">
                                    <i class="fas fa-users" style="font-size: 32px; color: #ccc; margin-bottom: 10px;"></i>
                                    <p>No users found. Adjust your filters or create a new user.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <a href="?page=1&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort; ?>" class="page-btn" <?php if($page == 1) echo 'style="visibility:hidden"'; ?>>&laquo;</a>
                    
                    <?php
                    // Show max 5 page buttons
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    if ($end_page - $start_page < 4 && $start_page > 1) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort; ?>" 
                           class="page-btn <?php if($i == $page) echo 'active'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <a href="?page=<?php echo $total_pages; ?>&role=<?php echo $role_filter; ?>&status=<?php echo $status_filter; ?>&sort=<?php echo $sort; ?>" 
                       class="page-btn" <?php if($page == $total_pages) echo 'style="visibility:hidden"'; ?>>&raquo;</a>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- User Details Modal -->
    <div id="user-details-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>User Details</h2>
            <div id="user-details-content" class="modal-body">
                <!-- Content will be loaded using JS -->
                <div class="loading">Loading user details...</div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Add New User</h2>
            <div class="modal-body">
                <form id="add-user-form" action="user_process.php" method="post">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="text" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" pattern="[0-9]{10,20}" title="Enter a valid phone number (10-20 digits)">
                    </div>
                    
                    <div class="form-group">
                        <label for="usertype">Role</label>
                        <select id="usertype" name="usertype" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" required>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">Create User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="edit-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Edit User</h2>
            <div class="modal-body">
                <form id="edit-user-form" action="user_process.php" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit-user-id" name="user_id">
                    
                    <div class="form-group">
                        <label for="edit-username">Username</label>
                        <input type="text" id="edit-username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-password">Password (leave empty to keep current)</label>
                        <input type="text" id="edit-password" name="password">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-phone">Phone</label>
                        <input type="text" id="edit-phone" name="phone" pattern="[0-9]{10,20}" title="Enter a valid phone number (10-20 digits)">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-usertype">Role</label>
                        <select id="edit-usertype" name="usertype" required>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-status">Status</label>
                        <select id="edit-status" name="status" required>
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="submit-btn">Update User</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="delete-user-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Delete User</h2>
            <div class="modal-body">
                <p>Are you sure you want to delete the user <strong id="delete-user-name"></strong>?</p>
                <p>This action cannot be undone.</p>
                
                <form id="delete-user-form" action="user_process.php" method="post">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" id="delete-user-id" name="user_id">
                    
                    <div class="modal-buttons">
                        <button type="button" class="cancel-btn close-modal">Cancel</button>
                        <button type="submit" class="delete-btn">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="admin.js"></script>
    <script src="admin_user_management.js"></script>
</body>
</html>
