<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as admin, if not redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

// Include database connection file
include 'config.php';

// Get admin information from session
$admin_username = $_SESSION['username'];

// Fetch books from database with category information
$books = [];
$categories = [];

// Query to get books with category names using JOIN
$query = "SELECT b.*, c.name AS category_name 
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.category_id 
          ORDER BY b.title ASC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

// Query to get categories
$categoryQuery = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
$categoryResult = $conn->query($categoryQuery);

if ($categoryResult && $categoryResult->num_rows > 0) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Close the database connection
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management | Library Admin</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_book_management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Library Admin</h2>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="admin_homepage.php">
                            <span class="icon"><i class="fas fa-home"></i></span>
                            <span class="text">Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_user_management.php">
                            <span class="icon"><i class="fas fa-users"></i></span>
                            <span class="text">User Management</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="admin_book_management.php">
                            <span class="icon"><i class="fas fa-book"></i></span>
                            <span class="text">Book Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_request.php">
                            <span class="icon"><i class="fas fa-exchange-alt"></i></span>
                            <span class="text">Book Reservation</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_room_management.php">
                            <span class="icon"><i class="fas fa-door-open"></i></span>
                            <span class="text">Room Management</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon logout-icon">
                    </span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-user">
                    <span class="username"><?php echo htmlspecialchars($admin_username); ?></span>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </header>

            <div class="dashboard">
                <h1 class="page-title"><i class="fas fa-book"></i> Book Management</h1>
                <p class="page-description">Add, edit, and manage the library's book collection.</p>
                
                <?php if (isset($_SESSION['message'])): ?>
                <div class="message <?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message']; 
                    // Clear the message after displaying
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Tab Navigation -->
                <div class="tab-container">
                    <div class="tabs">
                        <div class="tab active" data-tab="books-tab">Books</div>
                        <div class="tab" data-tab="categories-tab">Categories</div>
                        <div class="tab" data-tab="borrowing-tab">Borrowing</div>
                    </div>
                    
                    <!-- Books Tab -->
                    <div class="tab-content active" id="books-tab">
                        <div class="book-filters">
                            <div class="filter-container">
                                <div class="filter-group">
                                    <span>Filter by category:</span>
                                    <select class="filter-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['category_id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <span>Search:</span>
                                    <input type="text" class="book-search-input" placeholder="Search by title, author or ISBN...">
                                </div>
                                
                                <button type="button" id="reset-filters" class="btn-secondary">
                                    <i class="fas fa-undo"></i> Reset Filters
                                </button>
                            </div>
                            
                            <button class="create-book-btn">
                                <i class="fas fa-plus"></i>
                                Add New Book
                            </button>
                        </div>
                        
                        <div class="book-grid">
                            <?php if (!empty($books)): ?>
                                <?php foreach ($books as $book): ?>
                                    <div class="book-card">
                                        <div class="book-cover">
                                            <?php if (!empty($book['cover_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                            <?php else: ?>
                                                <img src="img/default-book-cover.png" alt="Default Book Cover">
                                            <?php endif; ?>
                                        </div>
                                        <div class="book-header">
                                            <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                            <span class="book-status status-<?php echo $book['status']; ?>">
                                                <?php echo ucfirst($book['status']); ?>
                                            </span>
                                        </div>
                                        <div class="book-body">
                                            <div class="book-info">
                                                <div class="info-item">
                                                    <span class="info-label">Author:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($book['author']); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Category:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($book['category_name'] ?? 'Uncategorized'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">ISBN:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-item">
                                                    <span class="info-label">Copies:</span>
                                                    <span class="info-value"><?php echo $book['available_copies']; ?>/<?php echo $book['total_copies']; ?> available</span>
                                                </div>
                                            </div>
                                            <div class="book-actions">
                                                <button class="book-action-btn edit-btn" data-id="<?php echo $book['book_id']; ?>">Edit</button>
                                                <button class="book-action-btn view-btn">View</button>
                                                <a href="process_delete_book.php?id=<?php echo $book['book_id']; ?>" 
                                                   class="book-action-btn delete-btn"
                                                   onclick="return confirm('Are you sure you want to delete this book? This action cannot be undone.');">
                                                    Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-data-message">No books found. Add a new book to get started.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Categories Tab -->
                    <div class="tab-content" id="categories-tab">
                        <div class="book-filters">
                            <button id="add-category-btn" class="create-book-btn">
                                <i class="fas fa-plus"></i>
                                Add New Category
                            </button>
                        </div>
                        
                        <?php if (!empty($categories)): ?>
                            <table class="category-table">
                                <thead>
                                    <tr>
                                        <th>Category ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?php echo $category['category_id']; ?></td>
                                            <td><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td><?php echo htmlspecialchars($category['description'] ?? 'No description'); ?></td>
                                            <td>
                                                <span class="book-status status-<?php echo $category['status']; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="book-actions">
                                                    <button class="book-action-btn edit-btn" data-id="<?php echo $category['category_id']; ?>">Edit</button>
                                                    <a href="delete_category.php?id=<?php echo $category['category_id']; ?>" 
                                                       class="book-action-btn delete-btn"
                                                       onclick="return confirm('Are you sure you want to delete this category? This may affect books in this category.');">
                                                        Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="no-data-message">No categories found. Add a new category to get started.</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Borrowing Tab -->
                    <div class="tab-content" id="borrowing-tab">
                        <div class="book-filters">
                            <div class="filter-container">
                                <div class="filter-group">
                                    <span>Filter by status:</span>
                                    <select class="filter-select">
                                        <option value="">All Status</option>
                                        <option value="borrowed">Borrowed</option>
                                        <option value="returned">Returned</option>
                                        <option value="overdue">Overdue</option>
                                    </select>
                                </div>
                                
                                <div class="filter-group">
                                    <span>Search user:</span>
                                    <input type="text" class="book-search-input" placeholder="Enter username...">
                                </div>
                            </div>
                            
                            <button type="button" class="filter-btn btn-primary">Apply</button>
                        </div>
                        
                        <!-- Empty borrowing tab content -->
                        <p class="no-data-message">No borrowing records found.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>    
    
    <!-- Add Book Modal -->
    <div id="add-book-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Book</h2>
            
            <form id="add-book-form" action="process_add_book.php" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Title*</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="author">Author*</label>
                    <input type="text" id="author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="category">Category*</label>
                    <select id="category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label for="summary">Summary</label>
                    <textarea id="summary" name="summary" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="total_copies">Total Copies*</label>
                        <input type="number" id="total_copies" name="total_copies" value="1" min="1" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="available_copies">Available Copies*</label>
                        <input type="number" id="available_copies" name="available_copies" value="1" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status*</label>
                    <select id="status" name="status" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-add" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Add Book</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- View Book Modal -->
    <div id="view-book-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-book-open"></i> Book Details</h2>
            <div id="book-details">
                <!-- Book details will be loaded here via AJAX -->
            </div>
        </div>
    </div>
    
    <!-- Edit Book Modal (updated with full form fields) -->
    <div id="edit-book-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Book</h2>
            
            <form id="edit-book-form" action="process_edit_book.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="edit-book-id" name="book_id" value="">
                
                <div class="form-group">
                    <label for="edit-title">Title*</label>
                    <input type="text" id="edit-title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-author">Author*</label>
                    <input type="text" id="edit-author" name="author" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-category">Category*</label>
                    <select id="edit-category" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach($categories as $category): ?>
                        <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Current Cover</label>
                    <div class="current-cover-container">
                        <img id="current-cover-preview" src="" alt="Current book cover" style="max-height: 100px; max-width: 100%;">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-cover-image">Update Cover Image</label>
                    <input type="file" id="edit-cover-image" name="cover_image" accept="image/*">
                    <small>Leave empty to keep current image</small>
                </div>
                
                <div class="form-group">
                    <label for="edit-summary">Summary</label>
                    <textarea id="edit-summary" name="summary" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit-isbn">ISBN</label>
                    <input type="text" id="edit-isbn" name="isbn">
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="edit-total-copies">Total Copies*</label>
                        <input type="number" id="edit-total-copies" name="total_copies" min="1" required>
                    </div>
                    
                    <div class="form-group half">
                        <label for="edit-available-copies">Available Copies*</label>
                        <input type="number" id="edit-available-copies" name="available_copies" min="0" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-status">Status*</label>
                    <select id="edit-status" name="status" required>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-edit" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Add Category Modal -->
    <div id="add-category-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-plus_circle"></i> Add New Category</h2>
            
            <form id="add-category-form" action="process_add_category.php" method="post">
                <div class="form-group">
                    <label for="category-name">Category Name*</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="category-description">Description</label>
                    <textarea id="category-description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="category-status">Status*</label>
                    <select id="category-status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-add-category" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="edit-category-modal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Category</h2>
            
            <form id="edit-category-form" action="process_edit_category.php" method="post">
                <input type="hidden" id="edit-category-id" name="category_id" value="">
                
                <div class="form-group">
                    <label for="edit-category-name">Category Name*</label>
                    <input type="text" id="edit-category-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-category-description">Description</label>
                    <textarea id="edit-category-description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="edit-category-status">Status*</label>
                    <select id="edit-category-status" name="status" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="cancel-edit-category" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="admin_book_management.js"></script>
</body>
</html>
