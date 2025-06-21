<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Include database connection
include 'config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Get loan ID from POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['loan_id']) || empty($data['loan_id'])) {
        throw new Exception('Reservation ID is required');
    }
    
    $loan_id = (int)$data['loan_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if reservation exists and belongs to the user
    $checkQuery = "SELECT l.*, b.book_id, b.title, b.available_copies 
                  FROM book_loans l
                  JOIN books b ON l.book_id = b.book_id
                  WHERE l.loan_id = ? AND l.id = ? AND l.status IN ('reserved', 'ready_for_pickup')";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('ii', $loan_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Reservation not found or cannot be cancelled');
    }
    
    $loan = $result->fetch_assoc();
    $current_status = $loan['status'];
    
    // Update reservation status to cancelled
    $cancelQuery = "UPDATE book_loans SET status = 'cancelled' WHERE loan_id = ?";
    $stmt = $conn->prepare($cancelQuery);
    $stmt->bind_param('i', $loan_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to cancel reservation');
    }
    
    // Only update book's available copies if the status was ready_for_pickup
    // This is the key change - we only increment if the book was in ready_for_pickup status
    if ($current_status === 'ready_for_pickup') {
        $newAvailableCopies = $loan['available_copies'] + 1;
        $updateBookQuery = "UPDATE books SET available_copies = ? WHERE book_id = ?";
        $stmt = $conn->prepare($updateBookQuery);
        $stmt->bind_param('ii', $newAvailableCopies, $loan['book_id']);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Reservation cancelled successfully',
        'book_title' => $loan['title']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close connection if open
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>