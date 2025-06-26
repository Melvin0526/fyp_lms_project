<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'config.php';

try {
    // Get filter parameters
    $category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Build the query
    $query = "SELECT b.*, c.name AS category_name 
              FROM books b 
              LEFT JOIN categories c ON b.category_id = c.category_id 
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add category filter
    if ($category_id > 0) {
        $query .= " AND b.category_id = ?";
        $params[] = $category_id;
        $types .= "i";
    }
    
    // Add search filter - modified to ignore spaces
    if (!empty($search)) {
        // Remove all spaces from search term
        $searchWithoutSpaces = str_replace(' ', '', $search);
        
        $query .= " AND (
                    REPLACE(b.title, ' ', '') LIKE ? OR 
                    REPLACE(b.author, ' ', '') LIKE ? OR 
                    REPLACE(b.isbn, ' ', '') LIKE ?
                    )";
        
        $search_param = "%{$searchWithoutSpaces}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "sss";
    }
    
    // Add ordering
    $query .= " ORDER BY b.title ASC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch results
    $books = [];
    while ($row = $result->fetch_assoc()) {
        // Process cover image path
        if (empty($row['cover_image'])) {
            $row['cover_image'] = 'img/default-book-cover.png';
        }
        
        $books[] = $row;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'books' => $books,
        'count' => count($books)
    ]);
    
    // Close statement
    $stmt->close();
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