<?php
// Include database connection file
include 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email from form
    $email = $conn->real_escape_string($_POST['reset_email']);
    
    // Check if email exists in database
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    
    if ($check_email->num_rows > 0) {
        // Email exists, store in session and redirect to reset password form
        session_start();
        $_SESSION['reset_email'] = $email;
        
        // Redirect to reset password form
        header("Location: reset_password.php");
        exit();
    } else {
        // Email not found
        header("Location: forgot_password.php?error=email_not_found");
        exit();
    }
}
?>
