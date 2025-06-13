<?php
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection and room functions
include 'config.php';
include 'room_functions.php';

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Update reservation statuses based on current time
updateReservationStatuses($conn);

// Process reservation cancellation
$notification_message = "";
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $reservation_id = $_GET['cancel'];
    $result = cancelReservation($conn, $reservation_id, $user_id);
    
    if ($result === true) {
        $notification_message = '<div class="success-message"><i class="fas fa-check-circle"></i> Reservation cancelled successfully!</div>';
    } else {
        $notification_message = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' . $result . '</div>';
    }
}

// Get user's room reservations (get all without limit)
$user_reservations = getUserReservations($conn, $user_id, 100);

// Handle filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filtered_reservations = [];

if ($filter === 'all') {
    $filtered_reservations = $user_reservations;
} else {
    foreach ($user_reservations as $reservation) {
        if ($reservation['status'] === $filter) {
            $filtered_reservations[] = $reservation;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation History | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="room_reservation.css">
    <link rel="stylesheet" href="reservation_history.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
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
                    <li><a href="book_reservation.php">Browse Book</a></li>
                    <li><a href="borrow_history.php">Borrow History</a></li>
                    <li><a href="room_reservation.php">Room Reservation</a></li>
                    <li><a href="reservation_history.php" class="active">Reservation History</a></li>
                </ul>
            </nav>
            <div class="user-menu">
                <div class="user-info">
                    <span><?php echo htmlspecialchars($username); ?></span>                    
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <?php if(isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                        <a href="admin_homepage.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="history-header">
                <h2><i class="fas fa-history"></i> Room Reservation History</h2>
                <p>View and manage all your past and upcoming room reservations</p>
                <?php if (!empty($notification_message)): ?>
                    <div class="notification-area">
                        <?php echo $notification_message; ?>
                    </div>
                <?php endif; ?>
            </section>
            
            <section class="history-content">
                <div class="filters-container">
                    <h3>Filter Reservations</h3>
                    <div class="filter-buttons">
                        <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-th-list"></i> All Reservations
                        </a>
                        <a href="?filter=confirmed" class="filter-btn <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> Confirmed
                        </a>
                        <a href="?filter=completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Completed
                        </a>
                        <a href="?filter=cancelled" class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </a>
                    </div>
                </div>

                <?php if (empty($filtered_reservations)): ?>
                    <div class="no-history">
                        <i class="far fa-calendar-times fa-3x"></i>
                        <p>No reservation history found for the selected filter.</p>
                        <a href="room_reservation.php" class="make-reservation-btn">Make a New Reservation</a>
                    </div>
                <?php else: ?>
                    <div class="reservations-table-wrapper">
                        <table class="reservations-table">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filtered_reservations as $reservation): ?>
                                    <tr>
                                        <td class="room-name">
                                            <div class="room-icon">
                                                <i class="fas fa-door-open"></i>
                                            </div>
                                            <?php echo htmlspecialchars($reservation['room_name']); ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($reservation['date'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($reservation['start_time'])) . ' - ' . 
                                                  date('h:i A', strtotime($reservation['end_time'])); ?></td>
                                        <td>
                                            <span class="status <?php echo $reservation['status']; ?>">
                                                <?php echo ucfirst($reservation['status']); ?>
                                            </span>
                                        </td>
                                        <td class="actions-cell">
                                            <?php if ($reservation['status'] === 'confirmed'): ?>
                                                <a href="?cancel=<?php echo $reservation['reservation_id']; ?>" 
                                                   class="cancel-btn" 
                                                   onclick="return confirm('Are you sure you want to cancel this reservation?')">
                                                    <i class="fas fa-times"></i> Cancel
                                                </a>
                                            <?php elseif ($reservation['status'] === 'completed'): ?>
                                                <span class="no-cancel-note">
                                                    <i class="fas fa-check-circle"></i> Completed
                                                </span>
                                            <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                                <span class="cancelled-note">
                                                    <i class="fas fa-times-circle"></i> Cancelled
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="history-notes">
                        <p><i class="fas fa-info-circle"></i> You can only cancel reservations with "Confirmed" status.</p>
                        <p><i class="fas fa-clock"></i> Reservations are automatically marked as "Completed" after the time slot ends.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Library System. All rights reserved.</p>
        </footer>
    </div>

    <script src="homepage.js"></script>
    <script src="reservation_history.js"></script>
</body>
</html>