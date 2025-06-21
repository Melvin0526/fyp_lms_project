<?php
// filepath: c:\xampp\htdocs\user_process.php
session_start();

// Check if the user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'config.php';

// Handle the form actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    // Add User
    if ($action == 'add') {
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
        $usertype = $conn->real_escape_string($_POST['usertype']);
        $status = $conn->real_escape_string($_POST['status']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        
        // First check if username or email already exists
        $check_sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // User or email already exists
            header("Location: admin_user_management.php?error=user_exists");
            exit();
        }
        
        // Insert the user
        $sql = "INSERT INTO users (username, email, password, usertype, status, phone) 
                VALUES ('$username', '$email', '$password', '$usertype', '$status', '$phone')";
                
        if ($conn->query($sql) === TRUE) {
            header("Location: admin_user_management.php?success=user_created");
        } else {
            header("Location: admin_user_management.php?error=create_failed&msg=" . urlencode($conn->error));
        }
    }
    
    // Edit User
    elseif ($action == 'edit') {
        $user_id = (int)$_POST['user_id'];
        $username = $conn->real_escape_string($_POST['username']);
        $email = $conn->real_escape_string($_POST['email']);
        $usertype = $conn->real_escape_string($_POST['usertype']);
        $status = $conn->real_escape_string($_POST['status']);
        $phone = $conn->real_escape_string($_POST['phone'] ?? '');
        
        // Check if email is used by another user
        $check_sql = "SELECT * FROM users WHERE email = '$email' AND id != $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Email already in use by another user
            header("Location: admin_user_management.php?error=email_exists");
            exit();
        }
        
        // Check if username is used by another user
        $check_username_sql = "SELECT * FROM users WHERE username = '$username' AND id != $user_id";
        $check_username_result = $conn->query($check_username_sql);
        
        if ($check_username_result->num_rows > 0) {
            // Username already in use by another user
            header("Location: admin_user_management.php?error=username_exists");
            exit();
        }
        
        // Start building the SQL statement
        $sql = "UPDATE users SET 
                username = '$username', 
                email = '$email', 
                usertype = '$usertype', 
                status = '$status',
                phone = '$phone'";
        
        // If password is provided, update it
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql .= ", password = '$password'";
        }
        
        $sql .= " WHERE id = $user_id";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: admin_user_management.php?success=user_updated");
        } else {
            header("Location: admin_user_management.php?error=update_failed&msg=" . urlencode($conn->error));
        }
    }
    
    // Delete User
    elseif ($action == 'delete') {
        $user_id = (int)$_POST['user_id'];
        
        // Check if we're deleting ourselves
        if ($user_id == $_SESSION['user_id']) {
            header("Location: admin_user_management.php?error=cannot_delete_self");
            exit();
        }
        
        $sql = "DELETE FROM users WHERE id = $user_id";
        
        if ($conn->query($sql) === TRUE) {
            header("Location: admin_user_management.php?success=user_deleted");
        } else {
            header("Location: admin_user_management.php?error=delete_failed&msg=" . urlencode($conn->error));
        }
    }
}

// Handle AJAX requests for user details
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_user_details') {
    $user_id = (int)$_GET['user_id'];
    
    $sql = "SELECT id, username, email, usertype, status, phone FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    exit();
}

// Close the database connection
$conn->close();
?>