<?php
// Start the session
session_start();

// Include database connection
include 'config.php';

// Get filter parameters
$category = isset($_GET['category']) ? $_GET['category'] : '';
$availability = isset($_GET['availability']) ? $_GET['availability'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query with category and search filters
$query = "SELECT b.*, c.name AS category_name 
          FROM books b
          LEFT JOIN categories c ON b.category_id = c.category_id
          WHERE 1=1";

// Add parameters
$params = [];
$types = "";

// Add category filter
if (!empty($category)) {
    $query .= " AND b.category_id = ?";
    $params[] = $category;
    $types .= "i";
}

// Add availability filter
if (!empty($availability)) {
    $query .= " AND b.status = ?";
    $params[] = $availability;
    $types .= "s";
}

// Add search filter (title, author, or ISBN)
if (!empty($search)) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";

    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Order by title
$query .= " ORDER BY b.title ASC";

try {
    $stmt = $conn->prepare($query);

    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $books = [];

    while ($row = $result->fetch_assoc()) {
        // Make sure the status is correctly set based on available copies
        // This ensures UI consistency even if the database status is incorrect
        if ($row['available_copies'] <= 0) {
            $row['status'] = 'unavailable';
        } else if ($row['status'] !== 'available' && $row['available_copies'] > 0) {
            // Optional: Update status to available if copies exist but status is wrong
            $row['status'] = 'available';
        }

        $books[] = $row;
    }

    // Return success response with books
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'books' => $books
    ]);
} catch (Exception $e) {
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error loading books: ' . $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>