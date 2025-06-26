<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Include database connection
include 'config.php';

$user_id = $_SESSION['user_id'];

try {
    // Get selected categories from POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['categories']) || !is_array($data['categories'])) {
        throw new Exception('Categories data is required');
    }
    
    $selected_categories = array_map('intval', $data['categories']);
    
    // Limit to 3 categories
    if (count($selected_categories) > 3) {
        throw new Exception('You can only select up to 3 categories');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Delete existing preferences for this user
    $delete_query = "DELETE FROM user_preferences WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    
    // Insert new preferences
    if (!empty($selected_categories)) {
        $insert_query = "INSERT INTO user_preferences (id, category_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        
        foreach ($selected_categories as $category_id) {
            $stmt->bind_param('ii', $user_id, $category_id);
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception('Error inserting preference: ' . $stmt->error);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Preferences saved successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    // Close connection if open
    if (isset($conn)) {
        $conn->close();
    }
}
?>