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

// For demo purposes, let's assume we have some books data
$books = [
    [
        'id' => 1, 
        'title' => 'The Great Gatsby', 
        'author' => 'F. Scott Fitzgerald', 
        'publisher' => 'Scribner',
        'isbn' => '9780743273565',
        'category' => 'Fiction',
        'available' => 5,
        'total' => 8,
        'image' => 'https://example.com/book1.jpg'
    ],
    [
        'id' => 2, 
        'title' => 'To Kill a Mockingbird', 
        'author' => 'Harper Lee', 
        'publisher' => 'J. B. Lippincott & Co.',
        'isbn' => '9780061120084',
        'category' => 'Fiction',
        'available' => 2,
        'total' => 6,
        'image' => 'https://example.com/book2.jpg'
    ],
    [
        'id' => 3, 
        'title' => 'Principles of Physics', 
        'author' => 'David Halliday', 
        'publisher' => 'Wiley',
        'isbn' => '9781118230732',
        'category' => 'Science',
        'available' => 3,
        'total' => 4,
        'image' => 'https://example.com/book3.jpg'
    ],
    [
        'id' => 4, 
        'title' => 'Introduction to Algorithms', 
        'author' => 'Thomas H. Cormen', 
        'publisher' => 'MIT Press',
        'isbn' => '9780262033848',
        'category' => 'Computer Science',
        'available' => 0,
        'total' => 5,
        'image' => 'https://example.com/book4.jpg'
    ],
    [
        'id' => 5, 
        'title' => '1984', 
        'author' => 'George Orwell', 
        'publisher' => 'Secker & Warburg',
        'isbn' => '9780451524935',
        'category' => 'Fiction',
        'available' => 7,
        'total' => 10,
        'image' => 'https://example.com/book5.jpg'
    ],
    [
        'id' => 6, 
        'title' => 'The Hobbit', 
        'author' => 'J.R.R. Tolkien', 
        'publisher' => 'Houghton Mifflin',
        'isbn' => '9780547928227',
        'category' => 'Fantasy',
        'available' => 4,
        'total' => 7,
        'image' => 'https://example.com/book6.jpg'
    ],
];

$categories = ['All Categories', 'Fiction', 'Non-Fiction', 'Science', 'Computer Science', 'History', 'Fantasy', 'Biography', 'Self-Help'];

// Close the database connection (in a real application)
if (isset($conn)) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management | Library Admin</title>    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="admin_book_management.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                            <span class="icon dashboard-icon"></span>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="admin_user_management.php">
                            <span class="icon users-icon"></span>
                            User Management
                        </a>
                    </li>
                    <li class="active">
                        <a href="admin_book_management.php">
                            <span class="icon books-icon"></span>
                            Book Management
                        </a>
                    </li>
                    <li>
                        <a href="admin_requests.php">
                            <span class="icon borrowing-icon"></span>
                            Requests
                        </a>
                    </li>
                    <li>
                        <a href="admin_room_management.php">
                            <span class="icon reports-icon"></span>
                            Room Management
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <span class="icon settings-icon"></span>
                            System Settings
                        </a>
                    </li>
                </ul>            </nav>            <div class="sidebar-footer">
                <a href="logout.php" class="logout-btn">
                    <span class="icon logout-icon"></span>
                    Logout
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-search">
                    <span class="search-icon"></span>
                    <input type="text" placeholder="Search books..." />
                </div>
                <div class="header-user">
                    <span class="notification-bell">
                        <span class="notification-count">3</span>
                    </span>
                    <div class="user-profile">
                        <span class="user-name"><?php echo htmlspecialchars($admin_username); ?></span>
                        <span class="user-role">Administrator</span>
                        <a href="homepage.php" class="switch-view">Switch to User View</a>
                    </div>
                </div>
            </header>

            <div class="dashboard">
                <h1 class="page-title">Book Management</h1>
                <p class="page-description">Add, edit, and manage the library's book collection.</p>
                
                <div class="book-management-container">
                    <div class="book-filters">
                        <div class="filter-group">
                            <select class="filter-select" id="category-filter">
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                            <select class="filter-select" id="availability-filter">
                                <option value="all">All Availability</option>
                                <option value="available">Available</option>
                                <option value="borrowed">Borrowed</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                            
                            <div class="view-toggle">
                                <button class="view-toggle-btn grid-view active" title="Grid View">
                                    <div class="grid-icon">
                                        <span></span><span></span>
                                        <span></span><span></span>
                                    </div>
                                </button>
                                <button class="view-toggle-btn list-view" title="List View">
                                    <div class="list-icon">
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </div>
                                </button>
                            </div>
                        </div>
                        
                        <button class="book-create-btn">
                            <span class="plus-icon"></span>
                            Add New Book
                        </button>
                    </div>
                    
                    <!-- Grid View (Default) -->
                    <div class="book-grid">
                        <?php foreach ($books as $book): ?>
                            <div class="book-card">
                                <div class="book-image">
                                    <?php if (!empty($book['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                    <?php else: ?>
                                        <div class="book-image-placeholder">📚</div>
                                    <?php endif; ?>
                                </div>
                                <div class="book-details">
                                    <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                    <p class="book-author">by <?php echo htmlspecialchars($book['author']); ?></p>
                                    <div class="book-meta">
                                        <span class="book-category"><?php echo htmlspecialchars($book['category']); ?></span>
                                        <div class="book-availability">
                                            <?php if ($book['available'] > 0): ?>
                                                <span class="availability-tag available"><?php echo $book['available']; ?>/<?php echo $book['total']; ?></span>
                                            <?php elseif ($book['available'] == 0 && $book['total'] > 0): ?>
                                                <span class="availability-tag borrowed">0/<?php echo $book['total']; ?></span>
                                            <?php else: ?>
                                                <span class="availability-tag unavailable">Unavailable</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="book-actions">
                                        <button class="book-action-btn view-btn" data-id="<?php echo $book['id']; ?>">View</button>
                                        <button class="book-action-btn edit-btn" data-id="<?php echo $book['id']; ?>">Edit</button>
                                        <button class="book-action-btn delete-btn" data-id="<?php echo $book['id']; ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Table View (Hidden by default) -->
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>ISBN</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo $book['id']; ?></td>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo htmlspecialchars($book['category']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td>
                                        <?php if ($book['available'] > 0): ?>
                                            <span class="availability-tag available"><?php echo $book['available']; ?>/<?php echo $book['total']; ?></span>
                                        <?php elseif ($book['available'] == 0 && $book['total'] > 0): ?>
                                            <span class="availability-tag borrowed">0/<?php echo $book['total']; ?></span>
                                        <?php else: ?>
                                            <span class="availability-tag unavailable">Unavailable</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="book-actions">
                                            <button class="book-action-btn view-btn" data-id="<?php echo $book['id']; ?>">View</button>
                                            <button class="book-action-btn edit-btn" data-id="<?php echo $book['id']; ?>">Edit</button>
                                            <button class="book-action-btn delete-btn" data-id="<?php echo $book['id']; ?>">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="pagination">
                        <button class="page-btn">&laquo;</button>
                        <button class="page-btn active">1</button>
                        <button class="page-btn">2</button>
                        <button class="page-btn">3</button>
                        <button class="page-btn">&raquo;</button>
                    </div>
                </div>
            </div>
        </main>
    </div>    
    
    <script src="admin.js"></script>
    <script src="admin_book_management.js"></script>
    </script>
</body>
</html>
