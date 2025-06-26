<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection to get categories
include 'config.php';

// Get user information from session
$username = $_SESSION['username'];
$email = $_SESSION['email'];

// Fetch categories for filter dropdown
$categories = [];
$categoryQuery = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
$categoryResult = $conn->query($categoryQuery);

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve and Pick Up | Library System</title>
    <link rel="stylesheet" href="homepage.css">
    <link rel="stylesheet" href="book_reservation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Library System (LMS)</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="book_reservation.php" class="active">Browse Book</a></li>
                    <li><a href="borrow_history.php">Borrow History</a></li>
                    <li><a href="room_reservation.php">Room Reservation</a></li>
                    <li><a href="reservation_history.php">Reservation History</a></li>
                </ul>
            </nav>
            <div class="user-menu">
                <div class="user-info">
                    <span><?php echo htmlspecialchars($username); ?></span>
                    <div class="dropdown-content">
                        <a href="profile.php">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="reservation-header">
                <h2>Book Reservation</h2>
                <p>Search for books, reserve them, and pick them up at your convenience. Each reservation is valid for 3 days.</p>
                <p class="reservation-rules"><i class="fas fa-info-circle"></i> Note: You can have up to 3 active book reservations at a time.</p>
                <p class="view-reservations-note">You can view and manage your reservations in your <a href="borrow_history.php">Borrow History</a>.</p>
            </section>

            <div class="form-container">
                <div class="search-section">
                    <div class="search-header">
                        <h3><i class="fas fa-search"></i> Search Books</h3>
                        <button id="clear-filters" class="clear-filters-btn">Clear All Filters</button>
                    </div>
                    <div class="search-box">
                        <input type="text" id="book-search" placeholder="Search by title, author, or ISBN...">
                        <button id="search-btn" class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                
                <div class="filter-section">
                    <div class="filter-group">
                        <label class="filter-label" for="category-filter">Category</label>
                        <select id="category-filter" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label" for="availability-filter">Availability</label>
                        <select id="availability-filter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Book Grid Section -->
            <section class="book-section">
                <div id="loading" class="loading-container">
                    <div class="loading-spinner"></div>
                </div>
                <div id="books-grid" class="book-grid">
                    <!-- Books will be loaded here dynamically -->
                </div>
                <div id="no-books" class="no-books-message" style="display:none;">
                    <i class="fas fa-book-open"></i>
                    <p>No books found matching your criteria.</p>
                </div>
                
                <!-- Pagination -->
                <div id="pagination" class="pagination">
                    <!-- Pagination buttons will be added here -->
                </div>
            </section>
        </main>
    </div>

    <!-- Book Detail Modal -->
    <div id="book-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="book-detail-content">
                <!-- Book details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h3>Confirm Reservation</h3>
            <p id="confirmation-message">Are you sure you want to reserve this book?</p>
            <div class="modal-buttons">
                <button id="cancel-reservation" class="cancel-modal-btn">Cancel</button>
                <button id="confirm-reservation" class="confirm-btn">Confirm Reservation</button>
            </div>
        </div>
    </div>

    <script src="book_reservation.js"></script>
</body>
</html>
