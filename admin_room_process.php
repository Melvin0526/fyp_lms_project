<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'config.php';

// Process room update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_room') {
    // Get form data
    $room_id = $_POST['room_id'];
    $room_name = $_POST['room_name'];
    $capacity = $_POST['capacity'];
    $features = $_POST['features'];
    
    // Validate inputs
    if (empty($room_id) || empty($room_name) || !is_numeric($capacity)) {
        $_SESSION['error_message'] = "Invalid room data provided.";
        header("Location: admin_room_management.php");
        exit();
    }
    
    // Update room in database
    $sql = "UPDATE rooms SET room_name = ?, capacity = ?, features = ? WHERE room_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $room_name, $capacity, $features, $room_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Room updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating room: " . $conn->error;
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}

// Process room creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_room') {
    // Get form data
    $room_name = $_POST['room_name'];
    $capacity = $_POST['capacity'];
    $features = $_POST['features'];
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : 1; // Default to active
    
    // Validate inputs
    if (empty($room_name) || !is_numeric($capacity) || $capacity < 1) {
        $_SESSION['error_message'] = "Invalid room data provided.";
        header("Location: admin_room_management.php");
        exit();
    }
    
    // Insert new room into database
    $sql = "INSERT INTO rooms (room_name, capacity, features, is_active) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisi", $room_name, $capacity, $features, $is_active);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "New room created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating room: " . $conn->error;
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}

// Handle toggle room visibility action
if (isset($_GET['toggle_visibility']) && is_numeric($_GET['toggle_visibility'])) {
    $room_id = $_GET['toggle_visibility'];
    
    // Get current status
    $check_sql = "SELECT is_active FROM rooms WHERE room_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $room_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $room = $result->fetch_assoc();
        $new_status = $room['is_active'] ? 0 : 1; // Toggle the status
        
        // Update the status
        $update_sql = "UPDATE rooms SET is_active = ? WHERE room_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_status, $room_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Room visibility updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating room visibility: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Room not found.";
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}
?>