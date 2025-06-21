<?php
// Start the session
session_start();

// Check if the user is logged in as admin
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
    // Get data from POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['loan_id']) || empty($data['loan_id'])) {
        throw new Exception('Loan ID is required');
    }
    
    if (!isset($data['status']) || empty($data['status'])) {
        throw new Exception('New status is required');
    }
    
    $loan_id = (int)$data['loan_id'];
    $new_status = $data['status'];
    
    // Validate status
    $valid_statuses = ['reserved', 'ready_for_pickup', 'borrowed', 'returned', 'cancelled', 'expired'];
    if (!in_array($new_status, $valid_statuses)) {
        throw new Exception('Invalid status value');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get current loan details
    $checkQuery = "SELECT l.*, b.book_id, b.title, b.available_copies 
                  FROM book_loans l
                  JOIN books b ON l.book_id = b.book_id
                  WHERE l.loan_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Loan not found');
    }
    
    $loan = $result->fetch_assoc();
    
    // Determine which fields to update based on new status
    $updateFields = "";
    $updateAvailableCopies = false;
    $increaseAvailableCopies = false;
    $decreaseAvailableCopies = false;
    
    switch ($new_status) {
        case 'ready_for_pickup':
            if ($loan['status'] !== 'reserved') {
                throw new Exception('Loan must be reserved before marking as ready for pickup');
            }
            $updateFields = ", ready_at = NOW()";
            
            // Check if the book has available copies
            if ($loan['available_copies'] < 1) {
                throw new Exception('No copies available for this book');
            }
            
            // Decrease available copies when marking as ready for pickup
            $decreaseAvailableCopies = true;
            break;
            
        case 'borrowed':
            if ($loan['status'] !== 'ready_for_pickup') {
                throw new Exception('Book must be ready for pickup before borrowing');
            }
            $updateFields = ", picked_up_at = NOW(), due_date = DATE_ADD(NOW(), INTERVAL 14 DAY)";
            break;
            
        case 'returned':
            if ($loan['status'] !== 'borrowed') {
                throw new Exception('Book must be borrowed before returning');
            }
            $updateFields = ", return_date = NOW()";
            
            // Increase available copies when returning
            $increaseAvailableCopies = true;
            break;
            
        case 'cancelled':
            if (!in_array($loan['status'], ['reserved', 'ready_for_pickup'])) {
                throw new Exception('Only reserved or ready for pickup loans can be cancelled');
            }
            
            // If cancelling a ready_for_pickup loan, increase available copies
            if ($loan['status'] === 'ready_for_pickup') {
                $increaseAvailableCopies = true;
            }
            break;
            
        case 'expired':
            if (!in_array($loan['status'], ['reserved', 'ready_for_pickup'])) {
                throw new Exception('Only reserved or ready for pickup loans can expire');
            }
            
            // If an expired loan was ready_for_pickup, increase available copies
            if ($loan['status'] === 'ready_for_pickup') {
                $increaseAvailableCopies = true;
            }
            break;
    }
    
    // Update loan status
    $updateQuery = "UPDATE book_loans SET status = ? $updateFields WHERE loan_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('si', $new_status, $loan_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0 && !$stmt->errno) {
        // No rows were affected, but that might be because values didn't change
        // Let's check if the loan still exists
        $checkQuery = "SELECT loan_id FROM book_loans WHERE loan_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param('i', $loan_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception('Loan not found');
        }
    } else if ($stmt->errno) {
        throw new Exception('Database error: ' . $stmt->error);
    }
    
    // Update available copies if needed
    // For decreasing copies (ready_for_pickup):
    if ($decreaseAvailableCopies) {
        $newAvailableCopies = $loan['available_copies'] - 1;
        $updateBookQuery = "UPDATE books 
                           SET available_copies = ?, 
                               status = CASE WHEN ? <= 0 THEN 'unavailable' ELSE 'available' END 
                           WHERE book_id = ?";
        $stmt = $conn->prepare($updateBookQuery);
        $stmt->bind_param('iii', $newAvailableCopies, $newAvailableCopies, $loan['book_id']);
        $stmt->execute();
    } 
    // For increasing copies (returned/cancelled):
    else if ($increaseAvailableCopies) {
        $newAvailableCopies = $loan['available_copies'] + 1;
        $updateBookQuery = "UPDATE books 
                           SET available_copies = ?, 
                               status = CASE WHEN ? > 0 THEN 'available' ELSE 'unavailable' END 
                           WHERE book_id = ?";
        $stmt = $conn->prepare($updateBookQuery);
        $stmt->bind_param('iii', $newAvailableCopies, $newAvailableCopies, $loan['book_id']);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Loan status updated successfully',
        'book_title' => $loan['title'],
        'new_status' => $new_status
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