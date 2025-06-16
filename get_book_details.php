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

// Check if book_id was provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    
    // Get book details with category name
    $sql = "SELECT b.*, c.name AS category_name 
            FROM books b 
            LEFT JOIN categories c ON b.category_id = c.category_id 
            WHERE b.book_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        
        // Format the data for response
        $response = [
            'success' => true,
            'book' => [
                'book_id' => $book['book_id'],
                'title' => $book['title'],
                'author' => $book['author'],
                'category_name' => $book['category_name'] ?? 'Uncategorized',
                'category_id' => $book['category_id'],
                'cover_image' => $book['cover_image'] ?? 'img/default-book-cover.png',
                'summary' => $book['summary'] ?? 'No summary available',
                'isbn' => $book['isbn'] ?? 'N/A',
                'total_copies' => $book['total_copies'],
                'available_copies' => $book['available_copies'],
                'status' => $book['status'],
                'date_added' => $book['date_added'] ?? null
                // Removed updated_at field
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Book not found'
        ];
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Invalid book ID'
    ];
}

// Close the database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>