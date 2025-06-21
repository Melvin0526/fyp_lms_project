<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database connection file
include 'config.php';

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 100; // Number of requests per page

try {
    // Build the query
    $query = "SELECT l.*, l.status, l.reserved_at, l.ready_at, 
              l.picked_up_at, l.due_date, l.return_date,
              b.title as book_title, u.username
              FROM book_loans l
              JOIN books b ON l.book_id = b.book_id
              JOIN users u ON l.id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    // Add status filter
    if (!empty($status)) {
        if ($status === 'overdue') {
            $query .= " AND l.status = 'borrowed' AND l.due_date < CURRENT_DATE()";
        } else if ($status === 'returned_late') {
            $query .= " AND l.status = 'returned' AND l.return_date > l.due_date";
        } else {
            $query .= " AND l.status = ?";
            $params[] = $status;
            $types .= "s";
        }
    }
    
    // Add type filter (reserve vs pickup)
    if (!empty($type)) {
        if ($type === 'reserve') {
            $query .= " AND l.status IN ('reserved', 'ready_for_pickup', 'cancelled', 'expired')";
        } else if ($type === 'pickup') {
            $query .= " AND l.status IN ('borrowed', 'returned')";
        }
    }
    
    // Add date filter (single date or range)
    if (!empty($date)) {
        $query .= " AND DATE(l.reserved_at) = ?";
        $params[] = $date;
        $types .= "s";
    } else if (!empty($start_date) && !empty($end_date)) {
        $query .= " AND DATE(l.reserved_at) BETWEEN ? AND ?";
        $params[] = $start_date;
        $params[] = $end_date;
        $types .= "ss";
    }
    
    // Add ordering
    $query .= " ORDER BY l.reserved_at DESC";
    
    // Add pagination
    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;
    $types .= "ii";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch results
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Check if book is overdue
        if ($row['status'] === 'borrowed' && !empty($row['due_date'])) {
            $due_date = new DateTime($row['due_date']);
            $today = new DateTime();
            
            // Remove time component for accurate date comparison
            $due_date->setTime(0, 0, 0);
            $today->setTime(0, 0, 0);
            
            if ($due_date < $today) {
                // Add an overdue flag (we don't change the status in database)
                $row['is_overdue'] = true;
                $row['days_overdue'] = $today->diff($due_date)->days;
            }
        }
        
        // Check if book was returned late
        if ($row['status'] === 'returned' && !empty($row['due_date']) && !empty($row['return_date'])) {
            $due_date = new DateTime($row['due_date']);
            $return_date = new DateTime($row['return_date']);
            
            // Remove time component for accurate date comparison
            $due_date->setTime(0, 0, 0);
            $return_date->setTime(0, 0, 0);
            
            if ($return_date > $due_date) {
                // Add a returned late flag
                $row['returned_late'] = true;
                $row['days_late'] = $return_date->diff($due_date)->days;
            }
        }
        
        $requests[] = $row;
    }
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM book_loans l WHERE 1=1";
    $countParams = [];
    $countTypes = "";
    
    // Add same filters to count query
    if (!empty($status)) {
        if ($status === 'overdue') {
            $countQuery .= " AND l.status = 'borrowed' AND l.due_date < CURRENT_DATE()";
        } else if ($status === 'returned_late') {
            $countQuery .= " AND l.status = 'returned' AND l.return_date > l.due_date";
        } else {
            $countQuery .= " AND l.status = ?";
            $countParams[] = $status;
            $countTypes .= "s";
        }
    }
    
    if (!empty($type)) {
        if ($type === 'reserve') {
            $countQuery .= " AND l.status IN ('reserved', 'ready_for_pickup', 'cancelled', 'expired')";
        } else if ($type === 'pickup') {
            $countQuery .= " AND l.status IN ('borrowed', 'returned')";
        }
    }
    
    if (!empty($date)) {
        $countQuery .= " AND DATE(l.reserved_at) = ?";
        $countParams[] = $date;
        $countTypes .= "s";
    } else if (!empty($start_date) && !empty($end_date)) {
        $countQuery .= " AND DATE(l.reserved_at) BETWEEN ? AND ?";
        $countParams[] = $start_date;
        $countParams[] = $end_date;
        $countTypes .= "ss";
    }
    
    $countStmt = $conn->prepare($countQuery);
    
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalRequests = $totalRow['total'];
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'total' => $totalRequests,
        'total_pages' => ceil($totalRequests / $perPage),
        'current_page' => $page
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    // Close connection
    if (isset($conn) && $conn->ping()) {
        $conn->close();
    }
}
?>