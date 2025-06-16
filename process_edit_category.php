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
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
    
    // Check if we're receiving the category_id correctly
    if ($category_id <= 0) {
        $_SESSION['message'] = "Error: Invalid or missing category ID";
        $_SESSION['message_type'] = "error";
        header("Location: admin_book_management.php");
        exit();
    }
    
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = isset($_POST['description']) ? mysqli_real_escape_string($conn, $_POST['description']) : null;
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Validate input
    if (empty($name)) {
        $_SESSION['message'] = "Category name is required.";
        $_SESSION['message_type'] = "error";
        header("Location: admin_book_management.php");
        exit();
    }
    
    // Check if category with same name already exists (excluding the current category)
    $check_sql = "SELECT * FROM categories WHERE name = ? AND category_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $name, $category_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Another category with this name already exists.";
        $_SESSION['message_type'] = "error";
        header("Location: admin_book_management.php");
        exit();
    }
    
    // Update the category
    $query = "UPDATE categories SET name = ?, description = ?, status = ? WHERE category_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $name, $description, $status, $category_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Category updated successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating category: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    
    $stmt->close();
    $conn->close();
    
    // Redirect back to category management
    header("Location: admin_book_management.php");
    exit();
} else {
    // If accessed without form submission, redirect
    header("Location: admin_book_management.php");
    exit();
}
?>