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
    // Get book ID from POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['book_id']) || empty($data['book_id'])) {
        throw new Exception('Book ID is required');
    }
    
    $book_id = (int)$data['book_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check active reservation count for user (max 3)
    $checkLimitQuery = "SELECT COUNT(*) as active_count 
                       FROM book_loans
                       WHERE id = ? AND status IN ('reserved', 'ready_for_pickup', 'borrowed')";
    $stmt = $conn->prepare($checkLimitQuery);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $limitResult = $stmt->get_result();
    $activeReservations = $limitResult->fetch_assoc()['active_count'];
    
    // Define max limit
    $maxAllowed = 3;
    
    if ($activeReservations >= $maxAllowed) {
        throw new Exception("You have reached the maximum limit of $maxAllowed active reservations/loans.");
    }
    
    // Check if book exists and is available
    $checkQuery = "SELECT title, available_copies, status FROM books WHERE book_id = ? AND status = 'available'";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Book not found or not available for reservation');
    }
    
    $book = $result->fetch_assoc();
    
    // Check if user already has this book reserved or borrowed
    $checkExistingQuery = "SELECT loan_id, status FROM book_loans 
                          WHERE id = ? AND book_id = ? 
                          AND status IN ('reserved', 'ready_for_pickup', 'borrowed')";
    $stmt = $conn->prepare($checkExistingQuery);
    $stmt->bind_param('ii', $user_id, $book_id);
    $stmt->execute();
    $existingResult = $stmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        $existingLoan = $existingResult->fetch_assoc();
        $status = $existingLoan['status'];
        $statusDisplay = str_replace('_', ' ', $status);
        throw new Exception("You already have this book $statusDisplay. You cannot reserve the same book twice.");
    }
    
    // Create reservation
    $insertQuery = "INSERT INTO book_loans (id, book_id, reserved_at, status) VALUES (?, ?, NOW(), 'reserved')";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param('ii', $user_id, $book_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to create reservation');
    }
    
    $loan_id = $stmt->insert_id;
    
    // REMOVED: No longer update available copies at reservation time
    // Will only update when status changes to ready_for_pickup
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Book reserved successfully',
        'loan_id' => $loan_id,
        'book_title' => $book['title']
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