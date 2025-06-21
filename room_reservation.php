<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection and room functions
include 'config.php';
include 'room_functions.php';

// Get user information from session
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Update reservation statuses based on current time
updateReservationStatuses($conn);

// Get all rooms from the database
$rooms = getRooms($conn);

// Process reservation form submission - Keep this for non-JavaScript submissions
$reservation_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $room_id = $_POST['room_id'];
    $reservation_date = $_POST['reservation_date'];
    $slot_id = $_POST['slot_id'];
    
    $result = createReservation($conn, $user_id, $room_id, $reservation_date, $slot_id);
    
    if ($result === true) {
        $reservation_message = '<div class="success-message"><i class="fas fa-check-circle"></i> Reservation successful!</div>';
        
        // Refresh page after success to clear the form
        header("Refresh: 2; URL=homepage.php");
    } else {
        $reservation_message = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' . $result . '</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Reservation | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="room_reservation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>My Library System (LMS)</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="book_reservation.php">Browse Book</a></li>
                    <li><a href="borrow_history.php">Borrow History</a></li>
                    <li><a href="room_reservation.php" class="active">Room Reservation</a></li>
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
            <section class="reservation-header">
                <h2>Discussion Room Reservation</h2>
                <p>Reserve a discussion room for your group study or meetings. Each reservation is for a 2-hour time slot.</p>
                <p class="reservation-rules"><i class="fas fa-info-circle"></i> Note: You can only book a room until one hour after its start time.</p>
                <p class="view-reservations-note">You can view and manage your reservations on the <a href="homepage.php">homepage</a>.</p>
                <?php if (!empty($reservation_message)): ?>
                    <div class="notification-area">
                        <?php echo $reservation_message; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="reservation-form">
                <div class="form-container">
                    <div class="date-picker">
                        <h3><i class="far fa-calendar-alt"></i> Select Date</h3>
                        <?php
                            // Calculate the maximum date (one month from today)
                            $maxDate = date('Y-m-d', strtotime('+1 month'));
                        ?>
                        <input type="date" id="reservation-date" 
                               min="<?php echo date('Y-m-d'); ?>" 
                               max="<?php echo $maxDate; ?>">
                        <small class="date-hint">Bookings available up to one month in advance</small>
                    </div>
                    
                    <div class="room-selection">
                        <h3><i class="fas fa-door-open"></i> Select Room</h3>
                        <div class="rooms-grid">
                            <?php if (empty($rooms)): ?>
                                <p>No discussion rooms are available at the moment. Please check back later.</p>
                            <?php else: ?>
                                <?php foreach ($rooms as $index => $room): ?>
                                    <?php 
                                        // Set CSS class for room image (fallback to numbered rooms if no image_url)
                                        $roomImageClass = !empty($room['image_url']) ? '' : 'room' . ($index + 1);
                                        $roomImageStyle = !empty($room['image_url']) ? 'background-image: url(\'' . htmlspecialchars($room['image_url']) . '\');' : '';
                                    ?>
                                    <div class="room-card" data-room-id="<?php echo $room['room_id']; ?>">
                                        <h4><?php echo htmlspecialchars($room['room_name']); ?></h4>
                                        <p>Capacity: <?php echo htmlspecialchars($room['capacity']); ?> people</p>
                                        <p>Features: <?php echo htmlspecialchars($room['features']); ?></p>
                                        <div class="room-image <?php echo $roomImageClass; ?>" <?php if (!empty($roomImageStyle)) echo 'style="' . $roomImageStyle . '"'; ?>></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="timeslot-selection" id="timeslot-selection" style="display: none;">
                        <h3><i class="far fa-clock"></i> Select Timeslot</h3>
                        <div class="timeslot-header">
                            <div class="timeslot-header-icon">
                                <i class="far fa-calendar-check"></i>
                            </div>
                            <div class="timeslot-header-text">
                                <h4>Available Time Slots</h4>
                                <p>Click on a time slot below to make your selection</p>
                            </div>
                        </div>
                        <div class="timeslots-container" id="timeslots-container">
                            <p>Please select a room and date to see available timeslots.</p>
                        </div>
                    </div>
                    
                    <div class="reservation-summary">
                        <h3><i class="fas fa-clipboard-list"></i> Reservation Summary</h3>
                        <form id="reservation-form" method="post" action="">
                            <input type="hidden" name="room_id" id="room_id_input">
                            <input type="hidden" name="reservation_date" id="reservation_date_input">
                            <input type="hidden" name="slot_id" id="slot_id_input">
                            <div class="summary-details">
                                <p><i class="fas fa-door-open"></i> <strong>Room:</strong> <span id="summary-room">Please select a room</span></p>
                                <p><i class="far fa-calendar-alt"></i> <strong>Date:</strong> <span id="summary-date">Please select a date</span></p>
                                <p><i class="far fa-clock"></i> <strong>Timeslot:</strong> <span id="summary-timeslot">Please select a timeslot</span></p>
                            </div>
                            <button type="submit" name="reserve" id="reserve-btn" class="reserve-btn" disabled>
                                <i class="fas fa-check-circle"></i> Reserve Now
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>

        <footer>
            <p>&copy; 2025 Library System. All rights reserved.</p>
        </footer>
    </div>

    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-calendar-check"></i> Confirm Reservation</h2>
            <div class="confirmation-details">
                <p><i class="fas fa-door-open"></i> <strong>Room:</strong> <span id="confirm-room"></span></p>
                <p><i class="far fa-calendar-alt"></i> <strong>Date:</strong> <span id="confirm-date"></span></p>
                <p><i class="far fa-clock"></i> <strong>Timeslot:</strong> <span id="confirm-timeslot"></span></p>
            </div>
            <div class="reservation-notice">
                <i class="fas fa-info-circle"></i> Please confirm your room reservation details above.
            </div>
            <div class="modal-buttons">
                <button id="confirm-btn" class="confirm-btn"><i class="fas fa-check"></i> Confirm Reservation</button>
                <button id="cancel-modal-btn" class="cancel-modal-btn"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </div>
    </div>

    <script src="room_reservation.js"></script>
</body>
</html>
