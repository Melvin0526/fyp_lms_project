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
include 'room_functions.php';

// Get admin information from session
$admin_username = $_SESSION['username'];

// Process cancellation requests
$notification_message = "";
if (isset($_GET['cancel_reservation']) && is_numeric($_GET['cancel_reservation'])) {
    $reservation_id = $_GET['cancel_reservation'];
    
    // Admin-specific cancellation function that bypasses user ownership check
    $sql = "UPDATE reservation SET status = 'cancelled' WHERE reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $reservation_id);
    
    if ($stmt->execute()) {
        $notification_message = '<div class="success-message">Reservation #' . $reservation_id . ' has been cancelled successfully.</div>';
    } else {
        $notification_message = '<div class="error-message">Failed to cancel reservation: ' . $conn->error . '</div>';
    }
}

// Get all room data
$rooms = getRooms($conn, true); // Pass true to include hidden rooms

// Get search parameters
$username_search = isset($_GET['username_search']) ? trim($_GET['username_search']) : '';
$room_search = isset($_GET['room_search']) ? trim($_GET['room_search']) : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Get all reservations with user data - modified to include search filters
$bookings = [];

// Build the SQL query based on your actual table structure
$sql = "SELECT r.reservation_id, r.id, r.room_id, r.slot_id, r.date, 
               r.status, r.created_at, rm.room_name, rm.capacity, rm.features,
               ts.start_time, ts.end_time, ts.display_text,
               u.username, u.email, u.phone
        FROM reservation r
        JOIN rooms rm ON r.room_id = rm.room_id
        LEFT JOIN timeslots ts ON r.slot_id = ts.slot_id
        LEFT JOIN users u ON r.id = u.id
        WHERE 1=1";

// Add filters if provided
if (!empty($username_search)) {
    $sql .= " AND u.username LIKE '%" . $conn->real_escape_string($username_search) . "%'";
}
if (!empty($room_search)) {
    $sql .= " AND rm.room_name LIKE '%" . $conn->real_escape_string($room_search) . "%'";
}
if (!empty($date_filter)) {
    $sql .= " AND r.date = '" . $conn->real_escape_string($date_filter) . "'";
}

// Add ordering
$sql .= " ORDER BY r.created_at DESC, r.date DESC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management | Library Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_room_management.css">
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
                        <a href="admin_book_management.php">
                            <span class="icon"><i class="fas fa-book"></i></span>
                            <span class="text">Books</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_user_management.php">
                            <span class="icon"><i class="fas fa-users"></i></span>
                            <span class="text">Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_borrowing_management.php">
                            <span class="icon"><i class="fas fa-exchange-alt"></i></span>
                            <span class="text">Borrowing</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="admin_room_management.php">
                            <span class="icon"><i class="fas fa-door-open"></i></span>
                            <span class="text">Rooms</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_settings.php">
                            <span class="icon"><i class="fas fa-cog"></i></span>
                            <span class="text">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon logout-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-search">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search rooms or bookings..." />
                </div>
                <div class="header-user">
                    <span class="user-greeting">Welcome, <?php echo htmlspecialchars($admin_username); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </header>

            <div class="dashboard">
                <h1 class="page-title"><i class="fas fa-door-open"></i> Room Management</h1>
                <p class="page-description">Manage discussion rooms and their bookings.</p>
                
                <?php if (!empty($notification_message)): ?>
                    <div class="notification-area">
                        <?php echo $notification_message; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <div class="tab-container">
                    <div class="tabs">
                        <div class="tab" data-tab="rooms-tab">Rooms</div>
                        <div class="tab" data-tab="timeslots-tab">Timeslots</div>
                        <div class="tab active" data-tab="bookings-tab">Bookings</div>
                    </div>
                    
                    <!-- Rooms Tab -->
                    <div class="tab-content" id="rooms-tab">
                        <div class="room-filters">
                            <button class="create-room-btn">
                                <i class="fas fa-plus"></i>
                                Add New Room
                            </button>
                        </div>
                        
                        <div class="room-grid">
                            <?php if (!empty($rooms)): ?>
                                <?php foreach ($rooms as $room): ?>
                                    <div class="room-card">
                                        <div class="room-header">
                                            <h3 class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                            <span class="room-status status-<?php echo $room['is_active'] ? 'available' : 'maintenance'; ?>">
                                                <?php echo $room['is_active'] ? 'Available' : 'Maintenance'; ?>
                                            </span>
                                        </div>
                                        <div class="room-body">
                                            <div class="room-info">
                                                <div class="info-item">
                                                    <span class="info-label">Capacity:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($room['capacity']); ?> People</span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Features:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($room['features']); ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="room-actions">
                                                <button class="room-action-btn edit-btn" data-id="<?php echo $room['room_id']; ?>" type="button">Edit</button>
                                                <a href="admin_room_process.php?toggle_visibility=<?php echo $room['room_id']; ?>" 
                                                   class="room-action-btn <?php echo $room['is_active'] ? 'hide-btn' : 'show-btn'; ?>" 
                                                   onclick="return confirm('Are you sure you want to <?php echo $room['is_active'] ? 'hide' : 'show'; ?> this room?')">
                                                    <?php echo $room['is_active'] ? 'Hide Room' : 'Show Room'; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-data-message">No rooms found. Add a new room to get started.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Timeslots Tab -->
                    <div class="tab-content" id="timeslots-tab">
                        <div class="room-filters">
                            <button class="create-room-btn" id="add-timeslot-btn">
                                <i class="fas fa-plus"></i>
                                Add New Timeslot
                            </button>
                        </div>
                        
                        <div class="timeslot-grid">
                            <?php 
                            // Get all timeslots
                            $sql = "SELECT * FROM timeslots ORDER BY start_time";
                            $timeslotsResult = $conn->query($sql);
                            
                            if ($timeslotsResult && $timeslotsResult->num_rows > 0): 
                                while ($timeslot = $timeslotsResult->fetch_assoc()):
                                    // Format times for display
                                    $start_time_display = date('h:i A', strtotime($timeslot['start_time']));
                                    $end_time_display = date('h:i A', strtotime($timeslot['end_time']));
                                    $status_class = $timeslot['is_active'] ? 'status-available' : 'status-maintenance';
                                    $status_text = $timeslot['is_active'] ? 'Active' : 'Inactive';
                            ?>
                            
                            <div class="timeslot-card">
                                <div class="room-header">
                                    <h3 class="room-name"><?php echo htmlspecialchars($timeslot['display_text']); ?></h3>
                                    <span class="room-status <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                                <div class="room-body">
                                    <div class="room-info">
                                        <div class="info-item">
                                            <span class="info-label">Start Time:</span>
                                            <span class="info-value"><?php echo $start_time_display; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">End Time:</span>
                                            <span class="info-value"><?php echo $end_time_display; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">ID:</span>
                                            <span class="info-value"><?php echo $timeslot['slot_id']; ?></span>
                                        </div>
                                    </div>
                                    <div class="room-actions">
                                        <button class="room-action-btn edit-timeslot-btn" data-id="<?php echo $timeslot['slot_id']; ?>" 
                                                data-start="<?php echo $timeslot['start_time']; ?>" 
                                                data-end="<?php echo $timeslot['end_time']; ?>" 
                                                data-display="<?php echo htmlspecialchars($timeslot['display_text']); ?>" 
                                                data-active="<?php echo $timeslot['is_active']; ?>" 
                                                type="button">Edit</button>
                                        
                                        <a href="admin_timeslot_process.php?toggle_visibility=<?php echo $timeslot['slot_id']; ?>" 
                                           class="room-action-btn <?php echo $timeslot['is_active'] ? 'hide-btn' : 'show-btn'; ?>" 
                                           onclick="return confirm('Are you sure you want to <?php echo $timeslot['is_active'] ? 'deactivate' : 'activate'; ?> this timeslot?')">
                                            <?php echo $timeslot['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <?php 
                                endwhile;
                            else: 
                            ?>
                                <p class="no-data-message">No timeslots found. Add a new timeslot to get started.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Bookings Tab -->
                    <div class="tab-content active" id="bookings-tab">
                        <div class="date-filters">
                            <form method="get" action="" id="date-filter-form">
                                <div class="filter-container">
                                    <div class="filter-group">
                                        <span class="date-filter-label">Filter by date:</span>
                                        <input type="date" class="date-picker" id="booking-date-filter" name="date" 
                                               value="<?php echo !empty($date_filter) ? $date_filter : ''; ?>">
                                    </div>
                                    <div class="filter-group">
                                        <span class="date-filter-label">Search by username:</span>
                                        <input type="text" class="username-input" id="username-search" name="username_search" 
                                               value="<?php echo !empty($username_search) ? htmlspecialchars($username_search) : ''; ?>" 
                                               placeholder="Enter username...">
                                    </div>
                                    <div class="filter-group">
                                        <span class="date-filter-label">Search by room:</span>
                                        <input type="text" class="room-input" id="room-search" name="room_search" 
                                               value="<?php echo !empty($room_search) ? htmlspecialchars($room_search) : ''; ?>" 
                                               placeholder="Enter room name...">
                                    </div>
                                    <div class="filter-group">
                                        <button type="submit" class="filter-btn">Apply Filters</button>
                                        <a href="admin_room_management.php" class="clear-filter-btn">Clear</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <?php if (!empty($bookings)): ?>
                            <table class="bookings-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Room</th>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo $booking['reservation_id']; ?></td>
                                            <td>
                                                <div class="user-info-cell">
                                                    <span class="user-name"><?php echo htmlspecialchars($booking['username'] ?? 'Unknown'); ?></span>
                                                    <span class="user-email"><?php echo htmlspecialchars($booking['email'] ?? ''); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['date'])); ?></td>
                                            <td>
                                                <?php if (!empty($booking['start_time']) && !empty($booking['end_time'])): ?>
                                                    <?php echo date('h:i A', strtotime($booking['start_time'])) . ' - ' . 
                                                          date('h:i A', strtotime($booking['end_time'])); ?>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars($booking['display_text'] ?? 'No time specified'); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="booking-status status-<?php echo strtolower($booking['status']); ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="booking-actions">
                                                    <?php if ($booking['status'] == 'confirmed'): ?>
                                                        <a href="?cancel_reservation=<?php echo $booking['reservation_id']; ?>" 
                                                           class="booking-action-btn cancel-btn"
                                                           onclick="return confirm('Are you sure you want to cancel this reservation?')">
                                                            Cancel
                                                        </a>
                                                    <?php endif; ?>
                                                    <button class="booking-action-btn view-btn" data-id="<?php echo $booking['reservation_id']; ?>">View</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-bookings-message">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Bookings Found</h3>
                                <p>No room bookings match your search criteria.</p>
                                <?php if (!empty($date_filter) || !empty($username_search)): ?>
                                    <a href="admin_room_management.php" class="clear-filter-link">Clear filters</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>    
    
    <!-- View Reservation Modal -->
    <div class="modal" id="view-reservation-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-info-circle"></i> Reservation Details</h2>
            <div id="reservation-details">
                <!-- Reservation details will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Edit Room Modal -->
    <div class="modal" id="edit-room-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Room</h2>
            <form id="edit-room-form" method="post" action="admin_room_process.php">
                <input type="hidden" id="edit-room-id" name="room_id" value="">
                <div class="form-group">
                    <label for="edit-room-name">Room Name:</label>
                    <input type="text" id="edit-room-name" name="room_name" required>
                </div>
                <div class="form-group">
                    <label for="edit-capacity">Capacity:</label>
                    <input type="number" id="edit-capacity" name="capacity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="edit-features">Features:</label>
                    <textarea id="edit-features" name="features" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <button type="button" id="cancel-edit" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add New Room Modal -->
    <div class="modal" id="add-room-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Room</h2>
            <form id="add-room-form" method="post" action="admin_room_process.php">
                <input type="hidden" name="action" value="add_room">
                <div class="form-group">
                    <label for="new-room-name">Room Name:</label>
                    <input type="text" id="new-room-name" name="room_name" required maxlength="100">
                </div>
                <div class="form-group">
                    <label for="new-capacity">Capacity:</label>
                    <input type="number" id="new-capacity" name="capacity" min="1" max="50" required>
                </div>
                <div class="form-group">
                    <label for="new-features">Features:</label>
                    <textarea id="new-features" name="features" rows="3" maxlength="1000" placeholder="Enter room features separated by commas (e.g., Whiteboard, Projector, WiFi)"></textarea>
                </div>
                <div class="form-group">
                    <label for="new-status">Status:</label>
                    <select id="new-status" name="is_active">
                        <option value="1" selected>Available</option>
                        <option value="0">Maintenance</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Create Room</button>
                    <button type="button" id="cancel-add" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Timeslot Modal -->
    <div class="modal" id="edit-timeslot-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Timeslot</h2>
            <form id="edit-timeslot-form" method="post" action="admin_timeslot_process.php">
                <input type="hidden" id="edit-timeslot-id" name="slot_id" value="">
                <div class="form-group">
                    <label for="edit-display-text">Display Name:</label>
                    <input type="text" id="edit-display-text" name="display_text" required>
                </div>
                <div class="form-group">
                    <label for="edit-start-time">Start Time:</label>
                    <input type="time" id="edit-start-time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="edit-end-time">End Time:</label>
                    <input type="time" id="edit-end-time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label for="edit-timeslot-status">Status:</label>
                    <select id="edit-timeslot-status" name="is_active">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <input type="hidden" name="action" value="update_timeslot">
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <button type="button" id="cancel-edit-timeslot" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add New Timeslot Modal -->
    <div class="modal" id="add-timeslot-modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Timeslot</h2>
            <form id="add-timeslot-form" method="post" action="admin_timeslot_process.php">
                <div class="form-group">
                    <label for="new-display-text">Display Name:</label>
                    <input type="text" id="new-display-text" name="display_text" required placeholder="e.g., Morning Slot">
                </div>
                <div class="form-group">
                    <label for="new-start-time">Start Time:</label>
                    <input type="time" id="new-start-time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label for="new-end-time">End Time:</label>
                    <input type="time" id="new-end-time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label for="new-timeslot-status">Status:</label>
                    <select id="new-timeslot-status" name="is_active">
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <input type="hidden" name="action" value="add_timeslot">
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Create Timeslot</button>
                    <button type="button" id="cancel-add-timeslot" class="btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="admin_room_management.js"></script>
</body>
</html>
