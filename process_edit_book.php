<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection
    include 'config.php';
    
    // Get form data and sanitize input
    $book_id = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
    
    // Debug: Check if we're receiving the book_id correctly
    if ($book_id <= 0) {
        $_SESSION['message'] = "Error: Invalid or missing book ID";
        $_SESSION['message_type'] = "error";
        header("Location: admin_book_management.php");
        exit();
    }
    
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $summary = isset($_POST['summary']) ? mysqli_real_escape_string($conn, $_POST['summary']) : null;
    $isbn = isset($_POST['isbn']) ? mysqli_real_escape_string($conn, $_POST['isbn']) : null;
    $total_copies = (int)$_POST['total_copies'];
    $available_copies = (int)$_POST['available_copies'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Debug: Log the data we're trying to update
    error_log("Updating book ID: $book_id with title: $title, author: $author, category: $category_id");
    
    // Check if book exists
    $check_sql = "SELECT * FROM books WHERE book_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows === 0) {
        $_SESSION['message'] = "Error: Book not found with ID: $book_id";
        $_SESSION['message_type'] = "error";
        header("Location: admin_book_management.php");
        exit();
    }
    
    // Get current book data
    $current_book = $check_result->fetch_assoc();
    $cover_image = $current_book['cover_image']; // Keep existing cover image by default
    
    // Handle cover image upload if one was provided
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "uploads/book_covers/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate a unique filename
        $file_extension = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('book_cover_') . '.' . $file_extension;
        $target_file = $target_dir . $unique_filename;
        
        // Check if file is an actual image
        $check = getimagesize($_FILES['cover_image']['tmp_name']);
        if ($check === false) {
            $error = "File is not an image.";
        }
        
        // Check file size (limit to 5MB)
        else if ($_FILES['cover_image']['size'] > 5000000) {
            $error = "File is too large. Maximum size is 5MB.";
        }
        
        // Check file type
        else if (!in_array(strtolower($file_extension), ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        
        // If all checks pass, try to upload the file
        else if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
            // If upload successful, delete old cover image if it exists and isn't the default
            if (!empty($current_book['cover_image']) && file_exists($current_book['cover_image']) && 
                strpos($current_book['cover_image'], 'default-book-cover') === false) {
                unlink($current_book['cover_image']);
            }
            $cover_image = $target_file;
        } else {
            $error = "Error uploading file.";
        }
    }
    
    // If no errors, update the book in the database
    if (!isset($error)) {
        $query = "UPDATE books SET 
                  title = ?, 
                  author = ?, 
                  category_id = ?, 
                  cover_image = ?, 
                  summary = ?, 
                  isbn = ?, 
                  total_copies = ?, 
                  available_copies = ?, 
                  status = ?
                  WHERE book_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssisssiisi", $title, $author, $category_id, $cover_image, $summary, $isbn, $total_copies, $available_copies, $status, $book_id);
        
        // Debug: Check for SQL errors
        if (!$stmt->execute()) {
            error_log("SQL Error: " . $stmt->error);
            $error = "Database error: " . $stmt->error;
        } else {
            // Success - affected rows should be 1
            if ($stmt->affected_rows > 0) {
                $success = true;
                error_log("Book updated successfully, ID: $book_id");
            } else {
                // No rows were updated - could be that no fields changed
                $success = true;
                error_log("No changes made to book ID: $book_id");
            }
        }
        
        $stmt->close();
    }
    
    // Close the database connection
    $conn->close();
    
    // Redirect with success or error message
    if (isset($success) && $success) {
        $_SESSION['message'] = "Book updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = isset($error) ? $error : "Unknown error updating book.";
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admin_book_management.php");
    exit();
} else {
    // If accessed without form submission, redirect to book management page
    $_SESSION['message'] = "Invalid request method.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_book_management.php");
    exit();
}
?>