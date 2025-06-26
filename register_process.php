<?php
// Include database connection file
include 'config.php';

// Set header to JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data and sanitize inputs
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        // Passwords don't match
        $response['status'] = 'error';
        $response['message'] = 'Passwords do not match. Please try again.';
        echo json_encode($response);
        exit();
    }

    // Validate password length
    if (strlen($password) < 8) {
        $response['status'] = 'error';
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        exit();
    }
    
    // Check if username already exists
    $check_username = $conn->query("SELECT * FROM users WHERE username = '$username'");
    if ($check_username->num_rows > 0) {
        // Username already exists
        $response['status'] = 'error';
        $response['message'] = 'Username already exists. Please choose another.';
        echo json_encode($response);
        exit();
    }
    
    // Check if email already exists
    $check_email = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($check_email->num_rows > 0) {
        // Email already exists
        $response['status'] = 'error';
        $response['message'] = 'Email already registered. Please use another email or login.';
        echo json_encode($response);
        exit();
    }
    
    // Hash password for security
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Create SQL query to insert user data
    $sql = "INSERT INTO users (username, email, phone, password) 
            VALUES ('$username', '$email', '$phone', '$hashed_password')";
      // Execute query and check if successful
    if ($conn->query($sql) === TRUE) {
        // Registration successful
        $response['status'] = 'success';
        $response['message'] = 'Registration successful! Redirecting to login page...';
        echo json_encode($response);
        exit();
    } else {
        // Error occurred
        $response['status'] = 'error';
        $response['message'] = 'Registration failed. Please try again later.';
        echo json_encode($response);
        exit();
    }
    
    // Close connection
    $conn->close();
}
?>
