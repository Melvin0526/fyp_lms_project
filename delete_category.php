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

// Check if category_id was provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    // Check if category exists
    $check_sql = "SELECT * FROM categories WHERE category_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $category_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Check if any books use this category
        $book_check_sql = "SELECT COUNT(*) as book_count FROM books WHERE category_id = ?";
        $book_check_stmt = $conn->prepare($book_check_sql);
        $book_check_stmt->bind_param("i", $category_id);
        $book_check_stmt->execute();
        $book_result = $book_check_stmt->get_result();
        $book_count = $book_result->fetch_assoc()['book_count'];
        
        if ($book_count > 0) {
            // Category is in use by books
            $_SESSION['message'] = "Cannot delete category: it is assigned to {$book_count} books. Please reassign these books first.";
            $_SESSION['message_type'] = "error";
        } else {
            // Safe to delete
            $delete_sql = "DELETE FROM categories WHERE category_id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['message'] = "Category deleted successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Error deleting category: " . $conn->error;
                $_SESSION['message_type'] = "error";
            }
        }
    } else {
        $_SESSION['message'] = "Category not found.";
        $_SESSION['message_type'] = "error";
    }
} else {
    $_SESSION['message'] = "Invalid category ID.";
    $_SESSION['message_type'] = "error";
}

// Close the database connection
$conn->close();

// Redirect back to category management
header("Location: admin_book_management.php");
exit();
?>