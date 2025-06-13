<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'config.php';

// Check if reservation ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid reservation ID']);
    exit();
}

$reservation_id = $_GET['id'];

// Fetch complete reservation details
// Updated SQL query based on your actual table structure
$sql = "SELECT r.reservation_id, r.id, r.room_id, r.slot_id, r.date, 
               r.status, r.created_at, rm.room_name, rm.capacity, rm.features, rm.is_active,
               ts.start_time, ts.end_time, ts.display_text,
               u.username, u.email, u.phone
        FROM reservation r
        JOIN rooms rm ON r.room_id = rm.room_id
        LEFT JOIN timeslots ts ON r.slot_id = ts.slot_id
        LEFT JOIN users u ON r.id = u.id
        WHERE r.reservation_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Reservation not found']);
    exit();
}

// Format the reservation details
$reservation = $result->fetch_assoc();

// Format date and times for display
$formattedData = [
    'reservation_id' => $reservation['reservation_id'],
    'room_name' => $reservation['room_name'],
    'room_capacity' => $reservation['capacity'],
    'room_features' => $reservation['features'],
    'room_status' => $reservation['is_active'] ? 'Available' : 'Maintenance',
    'date' => date('F d, Y', strtotime($reservation['date'])),
    'day_of_week' => date('l', strtotime($reservation['date'])),
    'time' => !empty($reservation['start_time']) && !empty($reservation['end_time']) 
        ? date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time'])) 
        : ($reservation['display_text'] ?? 'No time specified'),
    'status' => $reservation['status'],
    'created_at' => date('F d, Y h:i A', strtotime($reservation['created_at'])),
    'username' => $reservation['username'] ?? 'Unknown',
    'user_email' => $reservation['email'] ?? '',
    'user_phone' => $reservation['phone'] ?? 'Not provided'
];

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($formattedData);
?>