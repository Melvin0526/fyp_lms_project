<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'config.php';

// Check if book_id was provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    
    // Check if the book exists
    $check_sql = "SELECT * FROM books WHERE book_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        
        // Delete the book
        $delete_sql = "DELETE FROM books WHERE book_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $book_id);
        
        if ($delete_stmt->execute()) {
            // On successful deletion, delete the cover image if it exists
            if (!empty($book['cover_image']) && file_exists($book['cover_image']) && 
                strpos($book['cover_image'], 'default-book-cover') === false) {
                unlink($book['cover_image']);
            }
            
            $_SESSION['message'] = "Book deleted successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting book: " . $conn->error;
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Book not found.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Invalid book ID.";
    $_SESSION['message_type'] = "error";
}

// Close the database connection
$conn->close();

// Redirect back to book management page
header("Location: admin_book_management.php");
exit();
?>