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
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $summary = isset($_POST['summary']) ? mysqli_real_escape_string($conn, $_POST['summary']) : null;
    $isbn = isset($_POST['isbn']) ? mysqli_real_escape_string($conn, $_POST['isbn']) : null;
    $total_copies = (int)$_POST['total_copies'];
    $available_copies = (int)$_POST['available_copies'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Handle cover image upload if one was provided
    $cover_image = null;
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
        else if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
        
        // If all checks pass, try to upload the file
        else if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_file)) {
            $cover_image = $target_file;
        } else {
            $error = "Error uploading file.";
        }
    }
    
    // If no errors, insert the book into the database
    if (!isset($error)) {
        $query = "INSERT INTO books (title, author, category_id, cover_image, summary, isbn, total_copies, available_copies, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssisssiii", $title, $author, $category_id, $cover_image, $summary, $isbn, $total_copies, $available_copies, $status);
        
        if ($stmt->execute()) {
            // Success - redirect to book management page
            $success = true;
        } else {
            // Error inserting record
            $error = "Error adding book: " . $stmt->error;
        }
        
        $stmt->close();
    }
    
    // Close the database connection
    $conn->close();
    
    // Redirect with success or error message
    if (isset($success) && $success) {
        $_SESSION['message'] = "Book added successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = $error;
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admin_book_management.php");
    exit();
}
else {
    // If accessed without form submission, redirect to book management page
    header("Location: admin_book_management.php");
    exit();
}
?>