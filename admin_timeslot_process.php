<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection
include 'config.php';

/**
 * Format time for display in 12-hour format
 * 
 * @param string $startTime Time in 24-hour format (HH:MM:SS)
 * @param string $endTime Time in 24-hour format (HH:MM:SS)
 * @return string Formatted time like "10:00 AM - 12:00 PM"
 */
function formatTimeForDisplay($startTime, $endTime) {
    $startDateTime = new DateTime($startTime);
    $endDateTime = new DateTime($endTime);
    
    $formattedStart = $startDateTime->format('g:i A'); // 12-hour format with AM/PM
    $formattedEnd = $endDateTime->format('g:i A');
    
    return $formattedStart . ' - ' . $formattedEnd;
}

// Process timeslot update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_timeslot') {
    // Get form data
    $slot_id = $_POST['slot_id'];
    $display_text = $_POST['display_text'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_active = $_POST['is_active'];
    
    // Validate inputs
    if (empty($slot_id) || empty($start_time) || empty($end_time)) {
        $_SESSION['error_message'] = "Invalid timeslot data provided.";
        header("Location: admin_room_management.php");
        exit();
    }
    
    // Auto-generate display text if it's empty or matches old format
    $check_sql = "SELECT display_text, start_time, end_time FROM timeslots WHERE slot_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $slot_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $oldData = $result->fetch_assoc();
        $oldFormattedTime = formatTimeForDisplay($oldData['start_time'], $oldData['end_time']);
        
        // If display_text matches old time format or is empty, update it with new format
        if (empty($display_text) || $display_text == $oldFormattedTime || $display_text == $oldData['display_text']) {
            $display_text = formatTimeForDisplay($start_time, $end_time);
        }
    }
    
    // Update timeslot in database
    $sql = "UPDATE timeslots SET display_text = ?, start_time = ?, end_time = ?, is_active = ? WHERE slot_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $display_text, $start_time, $end_time, $is_active, $slot_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Timeslot updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating timeslot: " . $conn->error;
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}

// Process timeslot creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_timeslot') {
    // Get form data
    $display_text = $_POST['display_text'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_active = isset($_POST['is_active']) ? $_POST['is_active'] : 1; // Default to active
    
    // Validate inputs
    if (empty($start_time) || empty($end_time)) {
        $_SESSION['error_message'] = "Invalid timeslot data provided.";
        header("Location: admin_room_management.php");
        exit();
    }
    
    // If display text is empty, generate from times
    if (empty($display_text)) {
        $display_text = formatTimeForDisplay($start_time, $end_time);
    }
    
    // Insert new timeslot into database
    $sql = "INSERT INTO timeslots (display_text, start_time, end_time, is_active) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $display_text, $start_time, $end_time, $is_active);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "New timeslot created successfully!";
    } else {
        $_SESSION['error_message'] = "Error creating timeslot: " . $conn->error;
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}

// Handle toggle timeslot visibility action
if (isset($_GET['toggle_visibility']) && is_numeric($_GET['toggle_visibility'])) {
    $slot_id = $_GET['toggle_visibility'];
    
    // Get current status
    $check_sql = "SELECT is_active FROM timeslots WHERE slot_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $slot_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $timeslot = $result->fetch_assoc();
        $new_status = $timeslot['is_active'] ? 0 : 1; // Toggle the status
        
        // Update the status
        $update_sql = "UPDATE timeslots SET is_active = ? WHERE slot_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $new_status, $slot_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Timeslot visibility updated successfully!";
        } else {
            $_SESSION['error_message'] = "Error updating timeslot visibility: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Timeslot not found.";
    }
    
    // Redirect back to room management page
    header("Location: admin_room_management.php");
    exit();
}
?>