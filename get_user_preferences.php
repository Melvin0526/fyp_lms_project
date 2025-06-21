<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection
include 'config.php';

$user_id = $_SESSION['user_id'];

try {
    // Get user preferences
    $query = "SELECT p.category_id, c.name as category_name 
              FROM user_preferences p
              JOIN categories c ON p.category_id = c.category_id
              WHERE p.user_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $preferences = [];
    while ($row = $result->fetch_assoc()) {
        $preferences[] = $row;
    }
    
    // Get all available categories
    $all_categories_query = "SELECT category_id, name FROM categories ORDER BY name ASC";
    $categories_result = $conn->query($all_categories_query);
    
    $categories = [];
    while ($category = $categories_result->fetch_assoc()) {
        $categories[] = $category;
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'preferences' => $preferences,
        'categories' => $categories,
        'count' => count($preferences)
    ]);
    
} catch (Exception $e) {
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