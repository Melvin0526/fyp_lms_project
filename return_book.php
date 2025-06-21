<?php
// Start the session
session_start();

// Check if the user is logged in as admin (only admin can return books)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
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

try {
    // Get loan ID from POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['loan_id']) || empty($data['loan_id'])) {
        throw new Exception('Loan ID is required');
    }
    
    $loan_id = (int)$data['loan_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if the loan exists and is currently borrowed
    $checkQuery = "SELECT l.*, b.book_id, b.title, b.available_copies 
                  FROM book_loans l
                  JOIN books b ON l.book_id = b.book_id
                  WHERE l.loan_id = ? AND l.status = 'borrowed'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Loan not found or not eligible for return');
    }
    
    $loan = $result->fetch_assoc();
    
    // Update loan status to 'returned' and set return date
    $updateQuery = "UPDATE book_loans SET status = 'returned', return_date = NOW() WHERE loan_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('i', $loan_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to update loan status');
    }
    
    // Increase book's available copies and update status
    $newAvailableCopies = $loan['available_copies'] + 1;
    $updateBookQuery = "UPDATE books 
                   SET available_copies = ?, 
                       status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END
                   WHERE book_id = ?";
    $stmt = $conn->prepare($updateBookQuery);
    $stmt->bind_param('iii', $newAvailableCopies, $newAvailableCopies, $loan['book_id']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Book marked as returned successfully',
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