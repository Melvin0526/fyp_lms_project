<?php
// Start the session
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

// Include database connection
include 'config.php';

// Get query parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$query = isset($_GET['query']) ? $_GET['query'] : '';

// Ensure we have the required parameters
if (empty($type) || empty($query)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

$results = [];

try {
    if ($type === 'user') {
        // Search for users by username or email
        $searchTerm = "%$query%";
        $stmt = $conn->prepare("SELECT id, username, email FROM users 
                               WHERE username LIKE ? OR email LIKE ? 
                               ORDER BY username ASC LIMIT 10");
        $stmt->bind_param('ss', $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'value' => $row['username'],
                'label' => $row['username'] . ' (' . $row['email'] . ')'
            ];
        }
    } elseif ($type === 'book') {
        // Search for books by title, ISBN, or ID
        $searchTerm = "%$query%";
        $numQuery = is_numeric($query) ? (int)$query : 0;
        
        $stmt = $conn->prepare("SELECT book_id, title, author, isbn, available_copies 
                               FROM books 
                               WHERE title LIKE ? OR isbn LIKE ? OR book_id = ?
                               ORDER BY title ASC LIMIT 10");
        $stmt->bind_param('ssi', $searchTerm, $searchTerm, $numQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $available = $row['available_copies'] > 0;
            $status = $available ? "Available ({$row['available_copies']})" : "Not Available";
            
            $results[] = [
                'value' => $row['title'],
                'label' => "{$row['title']} by {$row['author']} (ISBN: {$row['isbn']}) - $status",
                'available' => $available
            ];
        }
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch (Exception $e) {
    // Return empty array in case of error
    header('Content-Type: application/json');
    echo json_encode([]);
} finally {
    // Close the database connection
    if (isset($conn)) {
        $conn->close();
    }
}