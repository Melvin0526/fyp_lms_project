<?php
// Start session to access session variables
session_start();

// Include required files
require_once 'config.php';
require_once 'room_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You must be logged in to make a reservation.']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Check required parameters
if (!isset($_POST['room_id']) || !isset($_POST['reservation_date']) || !isset($_POST['slot_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit();
}

// Get form data
$room_id = $_POST['room_id'];
$date = $_POST['reservation_date'];  // This will go into the date field in the database
$slot_id = $_POST['slot_id'];

// Validate inputs
if (!is_numeric($room_id) || !is_numeric($slot_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid input parameters.']);
    exit();
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid date format.']);
    exit();
}

// Connect to database
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Check if the timeslot is available
if (!isTimeslotAvailable($conn, $room_id, $date, $slot_id)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This timeslot is no longer available. Please select another timeslot or room.']);
    exit();
}

// Process the reservation
$result = createReservation($conn, $user_id, $room_id, $date, $slot_id);

if ($result === true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Reservation successful!']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $result]);
}

$conn->close();
?>