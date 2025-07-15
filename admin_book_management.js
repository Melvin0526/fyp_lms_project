document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            tabs.forEach(t => {
                t.classList.remove('active');
            });
            
            // Add active class to clicked tab and show its content
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Add Book Modal Functionality
    const addBookBtn = document.querySelector('.create-book-btn');
    const addBookModal = document.getElementById('add-book-modal');
    const cancelAddBtn = document.getElementById('cancel-add');
    
    if (addBookBtn && addBookModal) {
        // Open the add book modal when clicking "Add New Book" button
        addBookBtn.addEventListener('click', function() {
            // Clear the form
            document.getElementById('add-book-form').reset();
            
            // Show the modal
            addBookModal.style.display = 'block';
        });
        
        // Close modal when clicking cancel
        if (cancelAddBtn) {
            cancelAddBtn.addEventListener('click', function() {
                addBookModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeAddModal = addBookModal.querySelector('.close-modal');
        if (closeAddModal) {
            closeAddModal.addEventListener('click', function() {
                addBookModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addBookModal) {
                addBookModal.style.display = 'none';
            }
        });
    }
    
    // Edit Book Modal Functionality
    const editButtons = document.querySelectorAll('.edit-btn');
    const editBookModal = document.getElementById('edit-book-modal');
    
    if (editButtons.length > 0 && editBookModal) {
        // Handle edit button clicks
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-id');
                console.log("Editing book ID:", bookId); // Add this for debugging
                
                // Ensure we're setting the value correctly
                document.getElementById('edit-book-id').value = bookId;
                
                // Fetch book details from the server
                fetch(`get_book_details.php?id=${bookId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            
                            // Populate the form fields
                            document.getElementById('edit-title').value = book.title;
                            document.getElementById('edit-author').value = book.author;
                            document.getElementById('edit-category').value = book.category_id || '';
                            
                            // Set the current cover image preview if available
                            const coverPreview = document.getElementById('current-cover-preview');
                            if (coverPreview) {
                                coverPreview.src = book.cover_image;
                                coverPreview.style.display = 'block';
                            }
                            
                            document.getElementById('edit-summary').value = book.summary;
                            document.getElementById('edit-isbn').value = book.isbn;
                            document.getElementById('edit-total-copies').value = book.total_copies;
                            document.getElementById('edit-available-copies').value = book.available_copies;
                            document.getElementById('edit-status').value = book.status;
                            
                            // Show the modal
                            editBookModal.style.display = 'block';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching book details:', error);
                        alert('Failed to load book details. Please try again.');
                    });
            });
        });
        
        // Close modal when clicking cancel
        const cancelEditBtn = document.getElementById('cancel-edit');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function() {
                editBookModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeEditModal = editBookModal.querySelector('.close-modal');
        if (closeEditModal) {
            closeEditModal.addEventListener('click', function() {
                editBookModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editBookModal) {
                editBookModal.style.display = 'none';
            }
        });
    }
    
    // View Book Modal Functionality
    const viewButtons = document.querySelectorAll('.view-btn');
    const viewBookModal = document.getElementById('view-book-modal');
    
    if (viewButtons.length > 0 && viewBookModal) {
        // Handle view button clicks
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.closest('.book-card').querySelector('.edit-btn').getAttribute('data-id');
                const bookDetails = document.getElementById('book-details');
                
                // Show loading state
                bookDetails.innerHTML = '<div class="loading">Loading book details...</div>';
                viewBookModal.style.display = 'block';
                
                // Fetch book details from server
                fetch(`get_book_details.php?id=${bookId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            
                            // Format date_added if available
                            const dateAdded = book.date_added ? new Date(book.date_added).toLocaleDateString() : 'Unknown';
                            
                            // Build the details HTML with summary below author in the header
                            let html = `
                                <div class="book-detail-header">
                                    <div class="book-cover-preview">
                                        <img src="${book.cover_image}" alt="${book.title}">
                                    </div>
                                    <div class="book-header-info">
                                        <h3>${book.title}</h3>
                                        <p class="book-author">by ${book.author}</p>
                                        <div class="book-summary-preview">${book.summary}</div>
                                        <span class="book-status status-${book.status}">${book.status.charAt(0).toUpperCase() + book.status.slice(1)}</span>
                                    </div>
                                </div>
                                <div class="book-detail-body">
                                    <div class="detail-item">
                                        <span class="detail-label">Category:</span>
                                        <span class="detail-value">${book.category_name}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">ISBN:</span>
                                        <span class="detail-value">${book.isbn}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Available Copies:</span>
                                        <span class="detail-value">${book.available_copies} of ${book.total_copies}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Added on:</span>
                                        <span class="detail-value">${dateAdded}</span>
                                    </div>
                                </div>
                            `;
                            
                            bookDetails.innerHTML = html;
                        } else {
                            bookDetails.innerHTML = `<div class="error-message">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bookDetails.innerHTML = '<div class="error-message">Failed to load book details. Please try again.</div>';
                    });
            });
        });
        
        // Close modal with X button
        const closeViewModal = viewBookModal.querySelector('.close-modal');
        if (closeViewModal) {
            closeViewModal.addEventListener('click', function() {
                viewBookModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === viewBookModal) {
                viewBookModal.style.display = 'none';
            }
        });
    }
    
    // Category management functionality
    const addCategoryBtn = document.getElementById('add-category-btn');
    const addCategoryModal = document.getElementById('add-category-modal');
    
    if (addCategoryBtn && addCategoryModal) {
        addCategoryBtn.addEventListener('click', function() {
            addCategoryModal.style.display = 'block';
        });
        
        // Close modal with X button
        const closeCategoryModal = addCategoryModal.querySelector('.close-modal');
        if (closeCategoryModal) {
            closeCategoryModal.addEventListener('click', function() {
                addCategoryModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking cancel
        const cancelCategoryBtn = document.getElementById('cancel-add-category');
        if (cancelCategoryBtn) {
            cancelCategoryBtn.addEventListener('click', function() {
                addCategoryModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addCategoryModal) {
                addCategoryModal.style.display = 'none';
            }
        });
    }
    
    // Edit Category Modal Functionality
    const categoryEditButtons = document.querySelectorAll('#categories-tab .edit-btn');
    const editCategoryModal = document.getElementById('edit-category-modal');
    
    if (categoryEditButtons.length > 0 && editCategoryModal) {
        // Handle edit button clicks for categories
        categoryEditButtons.forEach(button => {
            button.addEventListener('click', function() {
                const categoryId = this.getAttribute('data-id');
                
                // Set the category ID in the form
                document.getElementById('edit-category-id').value = categoryId;
                
                // Fetch category details from the server
                fetch(`get_category_details.php?id=${categoryId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const category = data.category;
                            
                            // Populate the form fields
                            document.getElementById('edit-category-name').value = category.name;
                            document.getElementById('edit-category-description').value = category.description || '';
                            document.getElementById('edit-category-status').value = category.status;
                            
                            // Show the modal
                            editCategoryModal.style.display = 'block';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching category details:', error);
                        alert('Failed to load category details. Please try again.');
                    });
            });
        });
        
        // Close edit category modal with cancel button
        const cancelEditCategoryBtn = document.getElementById('cancel-edit-category');
        if (cancelEditCategoryBtn) {
            cancelEditCategoryBtn.addEventListener('click', function() {
                editCategoryModal.style.display = 'none';
            });
        }
        
        // Close edit category modal with X button
        const closeEditCategoryModal = editCategoryModal.querySelector('.close-modal');
        if (closeEditCategoryModal) {
            closeEditCategoryModal.addEventListener('click', function() {
                editCategoryModal.style.display = 'none';
            });
        }
        
        // Close edit category modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editCategoryModal) {
                editCategoryModal.style.display = 'none';
            }
        });
        
        // Form validation for edit category form
        const editCategoryForm = document.getElementById('edit-category-form');
        if (editCategoryForm) {
            editCategoryForm.addEventListener('submit', function(e) {
                const name = document.getElementById('edit-category-name').value.trim();
                
                if (!name) {
                    e.preventDefault();
                    alert("Category name is required.");
                }
            });
        }
    }
    
    // Form validation for book form
    // Form validation for book form with improved copies validation
    const bookForm = document.getElementById('add-book-form');
    if (bookForm) {
        bookForm.addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const author = document.getElementById('author').value.trim();
            const category = document.getElementById('category').value;
            const totalCopies = parseInt(document.getElementById('total_copies').value);
            const availableCopies = parseInt(document.getElementById('available_copies').value);
            
            let isValid = true;
            let errorMessage = '';
            
            if (!title) {
                errorMessage += "Book title is required.\n";
                isValid = false;
            }
            
            if (!author) {
                errorMessage += "Author name is required.\n";
                isValid = false;
            }
            
            if (!category) {
                errorMessage += "Please select a category.\n";
                isValid = false;
            }
            
            // Add validation for copies relationship
            if (availableCopies > totalCopies) {
                errorMessage += "Available copies cannot exceed total copies.\n";
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert("Please correct the following errors:\n" + errorMessage);
            }
        });
    }
    
    // Form validation for edit book form with improved copies validation
    const editBookForm = document.getElementById('edit-book-form');
    if (editBookForm) {
        editBookForm.addEventListener('submit', function(e) {
            const title = document.getElementById('edit-title').value.trim();
            const author = document.getElementById('edit-author').value.trim();
            const category = document.getElementById('edit-category').value;
            const totalCopies = parseInt(document.getElementById('edit-total-copies').value);
            const availableCopies = parseInt(document.getElementById('edit-available-copies').value);
            
            let isValid = true;
            let errorMessage = '';
            
            if (!title) {
                errorMessage += "Book title is required.\n";
                isValid = false;
            }
            
            if (!author) {
                errorMessage += "Author name is required.\n";
                isValid = false;
            }
            
            if (!category) {
                errorMessage += "Please select a category.\n";
                isValid = false;
            }
            
            // Add validation for copies relationship
            if (availableCopies > totalCopies) {
                errorMessage += "Available copies cannot exceed total copies.\n";
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert("Please correct the following errors:\n" + errorMessage);
            }
        });
    }
    
    // Improve the total copies input validation for both add and edit forms
    // to update available copies max in real-time
    const totalCopiesInput = document.getElementById('total_copies');
    const availableCopiesInput = document.getElementById('available_copies');
    
    if (totalCopiesInput && availableCopiesInput) {
        ['input', 'change'].forEach(eventType => {
            totalCopiesInput.addEventListener(eventType, function() {
                const totalCopies = parseInt(this.value) || 0;
                const availableCopies = parseInt(availableCopiesInput.value) || 0;
                
                // Ensure total copies is at least 1
                if (totalCopies < 1) {
                    this.value = 1;
                }
                
                // Ensure available copies doesn't exceed total copies
                if (availableCopies > totalCopies) {
                    availableCopiesInput.value = totalCopies;
                }
                availableCopiesInput.max = totalCopies;
            });
        });
        
        // Also validate available copies on input
        availableCopiesInput.addEventListener('input', function() {
            const totalCopies = parseInt(totalCopiesInput.value) || 0;
            const availableCopies = parseInt(this.value) || 0;
            
            // Ensure available copies doesn't exceed total copies
            if (availableCopies > totalCopies) {
                this.value = totalCopies;
            }
            
            // Ensure available copies is not negative
            if (availableCopies < 0) {
                this.value = 0;
            }
        });
    }
    
    // Also link available copies for edit form with improved validation
    const editTotalCopiesInput = document.getElementById('edit-total-copies');
    const editAvailableCopiesInput = document.getElementById('edit-available-copies');
    
    if (editTotalCopiesInput && editAvailableCopiesInput) {
        ['input', 'change'].forEach(eventType => {
            editTotalCopiesInput.addEventListener(eventType, function() {
                const totalCopies = parseInt(this.value) || 0;
                const availableCopies = parseInt(editAvailableCopiesInput.value) || 0;
                
                // Ensure total copies is at least 1
                if (totalCopies < 1) {
                    this.value = 1;
                }
                
                // Ensure available copies doesn't exceed total copies
                if (availableCopies > totalCopies) {
                    editAvailableCopiesInput.value = totalCopies;
                }
                editAvailableCopiesInput.max = totalCopies;
            });
        });
        
        // Also validate available copies on input
        editAvailableCopiesInput.addEventListener('input', function() {
            const totalCopies = parseInt(editTotalCopiesInput.value) || 0;
            const availableCopies = parseInt(this.value) || 0;
            
            // Ensure available copies doesn't exceed total copies
            if (availableCopies > totalCopies) {
                this.value = totalCopies;
            }
            
            // Ensure available copies is not negative
            if (availableCopies < 0) {
                this.value = 0;
            }
        });
    }
    
    // Book filtering functionality
    const categoryFilter = document.querySelector('.filter-select');
    const searchInput = document.querySelector('.book-search-input');
    const bookGrid = document.querySelector('.book-grid');

    // Function to load filtered books
    function loadFilteredBooks() {
        const category = categoryFilter ? categoryFilter.value : '';
        const searchQuery = searchInput ? searchInput.value.trim() : '';
        
        // Show loading indicator
        bookGrid.innerHTML = '<div class="loading">Loading books...</div>';
        
        // Remove any existing results info elements first
        const existingResultsInfo = document.querySelectorAll('.search-results-info');
        existingResultsInfo.forEach(el => el.remove());
        
        // Fetch filtered books from the server
        fetch(`get_filtered_books.php?category=${category}&search=${encodeURIComponent(searchQuery)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.books.length > 0) {
                        // Build HTML for book cards
                        let html = '';
                        data.books.forEach(book => {
                            html += `
                                <div class="book-card">
                                    <div class="book-cover">
                                        <img src="${book.cover_image || 'img/default-book-cover.png'}" alt="${book.title}">
                                    </div>
                                    <div class="book-header">
                                        <h3 class="book-title">${book.title}</h3>
                                        <span class="book-status status-${book.status}">
                                            ${book.status.charAt(0).toUpperCase() + book.status.slice(1)}
                                        </span>
                                    </div>
                                    <div class="book-body">
                                        <div class="book-info">
                                            <div class="info-item">
                                                <span class="info-label">Author:</span>
                                                <span class="info-value">${book.author}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Category:</span>
                                                <span class="info-value">${book.category_name || 'Uncategorized'}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">ISBN:</span>
                                                <span class="info-value">${book.isbn || 'N/A'}</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="info-label">Copies:</span>
                                                <span class="info-value">${book.available_copies}/${book.total_copies} available</span>
                                            </div>
                                        </div>
                                        <div class="book-actions">
                                            <button class="book-action-btn edit-btn" data-id="${book.book_id}">Edit</button>
                                            <button class="book-action-btn view-btn">View</button>
                                            <a href="process_delete_book.php?id=${book.book_id}" 
                                               class="book-action-btn delete-btn"
                                               onclick="return confirm('Are you sure you want to delete this book? This action cannot be undone.');">
                                                Delete
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        bookGrid.innerHTML = html;
                        
                        // Reattach event listeners for the new buttons
                        attachBookCardEventListeners();
                        
                        // Add a count of results above the grid - only add one instance
                        // No need to display "Found X books" text at all if you don't want it
                        // If you want to keep the count but with different text, modify the line below
                        
                        // For example, to completely remove the count message, comment out these lines:
                        /*
                        const resultsCount = document.createElement('div');
                        resultsCount.className = 'search-results-info';
                        resultsCount.textContent = `Found ${data.count} ${data.count === 1 ? 'book' : 'books'}`;
                        bookGrid.parentNode.insertBefore(resultsCount, bookGrid);
                        */
                        
                    } else {
                        // Create a more helpful no-results message
                        const category = categoryFilter && categoryFilter.options[categoryFilter.selectedIndex].text;
                        const searchTerm = searchInput ? searchInput.value.trim() : '';
                        
                        let message = '<i class="fas fa-search"></i>No books found';
                        
                        if (searchTerm && category && category !== 'All Categories') {
                            message += ` matching "${searchTerm}" in the "${category}" category.`;
                        } else if (searchTerm) {
                            message += ` matching "${searchTerm}".`;
                        } else if (category && category !== 'All Categories') {
                            message += ` in the "${category}" category.`;
                        } else {
                            message += '.';
                        }
                        
                        bookGrid.innerHTML = `<p class="no-data-message">${message}</p>`;
                    }
                } else {
                    bookGrid.innerHTML = `<div class="error-message">${data.message || 'An error occurred while loading books.'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                bookGrid.innerHTML = '<div class="error-message">Failed to load books. Please try again.</div>';
            });
    }

    // Function to attach event listeners to dynamically created book cards
    function attachBookCardEventListeners() {
        // Re-attach edit button listeners
        document.querySelectorAll('.book-card .edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-id');
                
                // Ensure we're setting the value correctly
                document.getElementById('edit-book-id').value = bookId;
                
                // Fetch book details from the server
                fetch(`get_book_details.php?id=${bookId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            
                            // Populate the form fields
                            document.getElementById('edit-title').value = book.title;
                            document.getElementById('edit-author').value = book.author;
                            document.getElementById('edit-category').value = book.category_id || '';
                            
                            // Set the current cover image preview if available
                            const coverPreview = document.getElementById('current-cover-preview');
                            if (coverPreview) {
                                coverPreview.src = book.cover_image;
                                coverPreview.style.display = 'block';
                            }
                            
                            document.getElementById('edit-summary').value = book.summary;
                            document.getElementById('edit-isbn').value = book.isbn;
                            document.getElementById('edit-total-copies').value = book.total_copies;
                            document.getElementById('edit-available-copies').value = book.available_copies;
                            document.getElementById('edit-status').value = book.status;
                            
                            // Show the modal
                            document.getElementById('edit-book-modal').style.display = 'block';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching book details:', error);
                        alert('Failed to load book details. Please try again.');
                    });
            });
        });

        // Re-attach view button listeners
        document.querySelectorAll('.book-card .view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.closest('.book-card').querySelector('.edit-btn').getAttribute('data-id');
                const bookDetails = document.getElementById('book-details');
                
                // Show loading state
                bookDetails.innerHTML = '<div class="loading">Loading book details...</div>';
                document.getElementById('view-book-modal').style.display = 'block';
                
                // Fetch book details from server
                fetch(`get_book_details.php?id=${bookId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const book = data.book;
                            
                            // Format date_added if available
                            const dateAdded = book.date_added ? new Date(book.date_added).toLocaleDateString() : 'Unknown';
                            
                            // Build the details HTML with summary below author in the header
                            let html = `
                                <div class="book-detail-header">
                                    <div class="book-cover-preview">
                                        <img src="${book.cover_image}" alt="${book.title}">
                                    </div>
                                    <div class="book-header-info">
                                        <h3>${book.title}</h3>
                                        <p class="book-author">by ${book.author}</p>
                                        <div class="book-summary-preview">${book.summary}</div>
                                        <span class="book-status status-${book.status}">${book.status.charAt(0).toUpperCase() + book.status.slice(1)}</span>
                                    </div>
                                </div>
                                <div class="book-detail-body">
                                    <div class="detail-item">
                                        <span class="detail-label">Category:</span>
                                        <span class="detail-value">${book.category_name}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">ISBN:</span>
                                        <span class="detail-value">${book.isbn}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Available Copies:</span>
                                        <span class="detail-value">${book.available_copies} of ${book.total_copies}</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Added on:</span>
                                        <span class="detail-value">${dateAdded}</span>
                                    </div>
                                </div>
                            `;
                            
                            bookDetails.innerHTML = html;
                        } else {
                            bookDetails.innerHTML = `<div class="error-message">${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        bookDetails.innerHTML = '<div class="error-message">Failed to load book details. Please try again.</div>';
                    });
            });
        });
        
        // Re-attach category edit button listeners if we're in the categories tab
        const newCategoryEditButtons = document.querySelectorAll('#categories-tab .edit-btn');
        if (newCategoryEditButtons.length > 0 && editCategoryModal) {
            newCategoryEditButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const categoryId = this.getAttribute('data-id');
                    
                    // Set the category ID in the form
                    document.getElementById('edit-category-id').value = categoryId;
                    
                    // Fetch category details from the server
                    fetch(`get_category_details.php?id=${categoryId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const category = data.category;
                                
                                // Populate the form fields
                                document.getElementById('edit-category-name').value = category.name;
                                document.getElementById('edit-category-description').value = category.description || '';
                                document.getElementById('edit-category-status').value = category.status;
                                
                                // Show the modal
                                editCategoryModal.style.display = 'block';
                            } else {
                                alert('Error: ' + data.message);
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching category details:', error);
                            alert('Failed to load category details. Please try again.');
                        });
                });
            });
        }
    }

    // Set up event listeners for filter inputs
    if (categoryFilter) {
        categoryFilter.addEventListener('change', loadFilteredBooks);
    }

    if (searchInput) {
        // Set up debounce for search input
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(loadFilteredBooks, 500); // Wait 500ms after typing stops
        });
    }

    // Add this code for the reset filters button:

    const resetFiltersBtn = document.getElementById('reset-filters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            if (categoryFilter) {
                categoryFilter.value = '';
            }
            if (searchInput) {
                searchInput.value = '';
            }
            loadFilteredBooks();
        });
    }
    
    // Add this code to highlight active filters:

    function updateFilterStyles() {
        // Update category filter style
        if (categoryFilter) {
            if (categoryFilter.value) {
                categoryFilter.classList.add('active');
            } else {
                categoryFilter.classList.remove('active');
            }
        }
        
        // Update search input style
        if (searchInput) {
            if (searchInput.value.trim()) {
                searchInput.classList.add('active');
            } else {
                searchInput.classList.remove('active');
            }
        }
    }

    // Call updateFilterStyles after each filter change
    // Add to categoryFilter event listener:
    categoryFilter.addEventListener('change', function() {
        loadFilteredBooks();
        updateFilterStyles();
    });

    // Add to searchInput event listener:
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadFilteredBooks();
            updateFilterStyles();
        }, 500);
    });

    // Add to resetFiltersBtn event listener:
    resetFiltersBtn.addEventListener('click', function() {
        if (categoryFilter) {
            categoryFilter.value = '';
        }
        if (searchInput) {
            searchInput.value = '';
        }
        loadFilteredBooks();
        updateFilterStyles();
    });

    // Call initially
    updateFilterStyles();
    
    // Load initial set of filtered books
    if (bookGrid) {
        // Only run if we're on the page with the book grid
        loadFilteredBooks();
    }
});
