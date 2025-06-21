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

// Check if loan ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid loan ID']);
    exit();
}

$loan_id = $_GET['id'];

try {
    // Fetch loan details
    $query = "SELECT l.*, b.title as book_title, b.author, b.isbn, b.cover_image, 
                     u.username, u.email, u.phone
              FROM book_loans l
              JOIN books b ON l.book_id = b.book_id
              JOIN users u ON l.id = u.id
              WHERE l.loan_id = ?";
                     
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $loan_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Loan not found']);
        exit();
    }
    
    // Get loan details
    $loan = $result->fetch_assoc();
    
    // Format dates for display
    $loan['reserved_at_formatted'] = date('F j, Y g:i A', strtotime($loan['reserved_at']));
    
    if ($loan['ready_at']) {
        $loan['ready_at_formatted'] = date('F j, Y g:i A', strtotime($loan['ready_at']));
    }
    
    if ($loan['borrowed_at']) {
        $loan['borrowed_at_formatted'] = date('F j, Y g:i A', strtotime($loan['borrowed_at']));
    }
    
    if ($loan['returned_at']) {
        $loan['returned_at_formatted'] = date('F j, Y g:i A', strtotime($loan['returned_at']));
    }
    
    if ($loan['due_date']) {
        $loan['due_date_formatted'] = date('F j, Y', strtotime($loan['due_date']));
        
        // Calculate days until due or overdue days
        $dueDate = new DateTime($loan['due_date']);
        $today = new DateTime();
        $interval = $today->diff($dueDate);
        
        if ($interval->invert === 0) {
            $loan['days_until_due'] = $interval->days;
            $loan['is_overdue'] = false;
        } else {
            $loan['overdue_days'] = $interval->days;
            $loan['is_overdue'] = true;
        }
    }
    
    // Format status for display
    switch($loan['status']) {
        case 'reserved':
            $loan['status_formatted'] = 'Reserved';
            break;
        case 'ready_for_pickup':
            $loan['status_formatted'] = 'Ready for Pickup';
            break;
        case 'borrowed':
            $loan['status_formatted'] = 'Borrowed';
            break;
        case 'returned':
            $loan['status_formatted'] = 'Returned';
            break;
        case 'cancelled':
            $loan['status_formatted'] = 'Cancelled';
            break;
        case 'expired':
            $loan['status_formatted'] = 'Expired';
            break;
        default:
            $loan['status_formatted'] = ucfirst($loan['status']);
    }
    
    // Return the details
    header('Content-Type: application/json');
    echo json_encode($loan);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close connection
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>