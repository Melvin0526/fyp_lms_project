<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'config.php';

$user_id = $_SESSION['user_id'];
$max_allowed = 3; // Set the maximum allowed reservations to 3

try {
    // Fetch user's active reservations/loans
    $query = "SELECT l.*, b.title, b.author, b.cover_image, b.isbn 
              FROM book_loans l
              JOIN books b ON l.book_id = b.book_id
              WHERE l.id = ? AND l.status IN ('reserved', 'ready_for_pickup', 'borrowed')
              ORDER BY l.reserved_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reservations = [];
    $today = new DateTime();
    $today->setTime(0, 0, 0); // Reset time component for accurate comparison
    
    while ($row = $result->fetch_assoc()) {
        // Process cover image path
        if (empty($row['cover_image'])) {
            $row['cover_image'] = 'img/default-book-cover.png';
        }
        
        // Calculate days remaining or overdue
        if ($row['status'] === 'borrowed' && !empty($row['due_date'])) {
            $dueDate = new DateTime($row['due_date']);
            $dueDate->setTime(0, 0, 0); // Reset time component
            
            $diff = $today->diff($dueDate);
            $diffDays = (int)$diff->format('%R%a');
            
            if ($diffDays < 0) {
                // Book is overdue
                $row['days_remaining'] = 'Overdue by ' . abs($diffDays) . ' day' . (abs($diffDays) !== 1 ? 's' : '');
                $row['is_overdue'] = true;
            } else {
                // Book is not yet due
                $row['days_remaining'] = $diffDays . ' day' . ($diffDays !== 1 ? 's' : '') . ' remaining';
            }
        } else if ($row['status'] === 'ready_for_pickup') {
            // Calculate days remaining for pickup
            $readyDate = new DateTime($row['ready_at']);
            $readyDate->setTime(0, 0, 0);
            $readyDate->modify('+3 days'); // Assuming 3 days to pick up
            
            $diff = $today->diff($readyDate);
            $diffDays = (int)$diff->format('%R%a');
            
            if ($diffDays < 0) {
                $row['days_remaining'] = 'Pickup overdue by ' . abs($diffDays) . ' day' . (abs($diffDays) !== 1 ? 's' : '');
            } else {
                $row['days_remaining'] = $diffDays . ' day' . ($diffDays !== 1 ? 's' : '') . ' to pick up';
            }
        } else {
            $row['days_remaining'] = 'Awaiting approval';
        }
        
        $reservations[] = $row;
    }
    
    // Count active reservations
    $active_count = count($reservations);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reservations' => $reservations,
        'active_count' => $active_count,
        'max_allowed' => $max_allowed
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    // Close connection if open
    if (isset($conn)) {
        $conn->close();
    }
}
?>