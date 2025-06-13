<?php
// Set Malaysia timezone at the very beginning
date_default_timezone_set('Asia/Kuala_Lumpur');

// Script to get available timeslots for a room on a specific date
// Turn off all PHP error reporting to prevent it from interfering with JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header right at the start
header('Content-Type: application/json');

// Start session to access session variables
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'You must be logged in to view available timeslots.']);
    exit();
}

// Include required files
require_once 'config.php';
require_once 'room_functions.php';

// Check if required parameters are sent
if (!isset($_GET['room_id']) || !isset($_GET['date'])) {
    echo json_encode(['error' => 'Missing required parameters.']);
    exit();
}

// Get parameters
$roomId = $_GET['room_id'];
$date = $_GET['date'];

// Validate inputs
if (!is_numeric($roomId)) {
    echo json_encode(['error' => 'Invalid room ID.']);
    exit();
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format.']);
    exit();
}

// Check if date is in the past
$today = date('Y-m-d');
if ($date < $today) {
    echo json_encode(['error' => 'Cannot view timeslots for past dates.']);
    exit();
}

// Connect to database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

try {
    // Get available timeslots using our function
    $availableTimeslots = getAvailableTimeslots($conn, $roomId, $date);
    
    // Check if this is today's date
    $isToday = ($date === $today);
    
    // Return response with properly formatted Malaysian time
    echo json_encode([
        'success' => true,
        'room_id' => $roomId,
        'date' => $date,
        'is_today' => $isToday,
        'server_time' => date('h:i:s A') . ' MYT', // Format with AM/PM and add Malaysia timezone indicator
        'timezone' => 'Asia/Kuala_Lumpur', // Let client know the timezone
        'timeslots' => $availableTimeslots
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error fetching timeslots: ' . $e->getMessage(),
        'details' => 'Please contact the administrator.'
    ]);
}

$conn->close();
?>