<?php
// Start session
session_start();

// Include database connection file
include 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if email is in session
    if (!isset($_SESSION['reset_email'])) {
        header("Location: forgot_password.php?error=invalid_request");
        exit();
    }
    
    // Get form data and sanitize inputs
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate email matches session
    if ($email !== $_SESSION['reset_email']) {
        header("Location: forgot_password.php?error=invalid_request");
        exit();
    }
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        header("Location: reset_password.php?error=password_mismatch");
        exit();
    }
    
    // Hash new password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Update password
    $update_password = $conn->query("UPDATE users SET password = '$hashed_password' WHERE email = '$email'");
    
    if ($update_password) {
        // Password updated successfully
        // Clear the session
        unset($_SESSION['reset_email']);
        
        header("Location: login.php?success=password_reset");
        exit();
    } else {
        // Error updating password
        header("Location: reset_password.php?error=system_error");
        exit();
    }
}
?>
