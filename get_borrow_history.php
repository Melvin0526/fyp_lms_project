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

try {
    // Fetch user's borrowing history with additional fields for picked_up_at and overdue status
    // Added c.name as category_name to include book category
    $query = "SELECT l.*, b.title, b.author, b.cover_image, b.isbn, 
                    c.name AS category_name,
                    CASE WHEN l.status = 'returned' AND l.return_date > l.due_date THEN 1 ELSE 0 END as was_overdue
              FROM book_loans l
              JOIN books b ON l.book_id = b.book_id
              LEFT JOIN categories c ON b.category_id = c.category_id
              WHERE l.id = ? AND l.status IN ('returned', 'cancelled', 'expired')
              ORDER BY l.reserved_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Check if this was returned late
        if ($row['status'] === 'returned' && !empty($row['return_date']) && !empty($row['due_date'])) {
            $returnDate = new DateTime($row['return_date']);
            $dueDate = new DateTime($row['due_date']);
            
            if ($returnDate > $dueDate) {
                $row['was_overdue'] = true;
                
                // Calculate days overdue
                $interval = $returnDate->diff($dueDate);
                $row['days_late'] = $interval->days;
            }
        }
        
        $history[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'history' => $history,
        'count' => count($history)
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