<?php
// Include database connection file
include 'config.php';

// Initialize variables
$error = "";

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $username_or_email = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    
    // Check if input is username or email
    $sql = "SELECT * FROM users WHERE username = '$username_or_email' OR email = '$username_or_email'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        // User found
        $user = $result->fetch_assoc();
          // Verify password
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a session
            session_start();
            
            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            
            // Check user role and redirect accordingly
            if ($user['usertype'] == 'admin') {
                $_SESSION['is_admin'] = true;
                header("Location: admin_homepage.php");
            } else {
                header("Location: homepage.php");
            }
            exit();
        } else {
            // Password is incorrect
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } else {
        // User not found
        header("Location: login.php?error=user_not_found");
        exit();
    }
    
    // Close database connection
    $conn->close();
}
?>