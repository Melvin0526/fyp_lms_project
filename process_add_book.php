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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category_id = $_POST['category_id'] ?? null;
    $summary = $_POST['summary'] ?? '';
    $isbn = $_POST['isbn'] ?? '';
    $total_copies = $_POST['total_copies'] ?? 1;
    $available_copies = $_POST['available_copies'] ?? $total_copies;
    
    // Automatically determine status based on available copies
    $status = ($available_copies > 0) ? 'available' : 'unavailable';
    
    // Debug - check what we're receiving
    error_log("Status from form: " . $status);
    
    // Handle cover image upload if any
    $cover_image = "img/default-book-cover.png"; // Default image
    
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/book_covers/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate a unique filename
        $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('book_') . "." . $file_extension;
        $upload_path = $upload_dir . $file_name;
        
        // Move the uploaded file
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
            $cover_image = $upload_path;
        } else {
            $_SESSION['message'] = "Error uploading cover image.";
            $_SESSION['message_type'] = "error";
            header("Location: admin_book_management.php");
            exit();
        }
    }
    
    // Insert book data into the database - ensure status field is included
    $sql = "INSERT INTO books (title, author, category_id, summary, isbn, total_copies, available_copies, cover_image, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Include status in the bind_param
        $stmt->bind_param("ssissiiss", $title, $author, $category_id, $summary, $isbn, $total_copies, $available_copies, $cover_image, $status);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Book added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding book: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        
        $stmt->close();
    } else {
        $_SESSION['message'] = "Error preparing statement: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    
    $conn->close();
    header("Location: admin_book_management.php");
    exit();
}
?>