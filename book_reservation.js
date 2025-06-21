// JavaScript for the book reservation system
document.addEventListener('DOMContentLoaded', function() {

    // User dropdown menu functionality
    const userInfo = document.querySelector('.user-info');
    const dropdownContent = document.querySelector('.dropdown-content');
    
    // Add click listener to document
    document.addEventListener('click', function(event) {
        // If click is outside the user menu, close the dropdown
        if (!userInfo.contains(event.target)) {
            dropdownContent.style.display = 'none';
        }
    });
    
    // Toggle dropdown on user info click
    userInfo.addEventListener('click', function(event) {
        event.stopPropagation();
        const isDisplayed = dropdownContent.style.display === 'block';
        dropdownContent.style.display = isDisplayed ? 'none' : 'block';
    });
    
    // Get elements - add clear filters button
    const bookModal = document.getElementById('book-modal');
    const confirmationModal = document.getElementById('confirmation-modal');
    const closeButtons = document.querySelectorAll('.close-modal');
    const bookSearch = document.getElementById('book-search');
    const searchBtn = document.getElementById('search-btn');
    const clearFiltersBtn = document.getElementById('clear-filters');
    const categoryFilter = document.getElementById('category-filter');
    const availabilityFilter = document.getElementById('availability-filter');
    const booksGrid = document.getElementById('books-grid');
    const loadingSpinner = document.getElementById('loading');
    const noBooks = document.getElementById('no-books');
    const pagination = document.getElementById('pagination');
    
    // Variables for pagination
    let currentPage = 1;
    const booksPerPage = 12;
    let totalBooks = 0;
    let allBooks = [];
    
    // Close modals when clicking on X
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            bookModal.style.display = 'none';
            confirmationModal.style.display = 'none';
        });
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === bookModal) {
            bookModal.style.display = 'none';
        }
        if (e.target === confirmationModal) {
            confirmationModal.style.display = 'none';
        }
    });
    
    // Function to clear all filters
    function clearAllFilters() {
        // Reset all filter inputs
        if (bookSearch) bookSearch.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (availabilityFilter) availabilityFilter.value = '';
        
        // Reset to first page
        currentPage = 1;
        
        // Load books with cleared filters
        loadBooks();
    }
    
    // Set up clear filters functionality
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearAllFilters);
    }
    
    // Set up search functionality
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            currentPage = 1; // Reset to first page on new search
            loadBooks();
        });
    }
    
    // Handle enter key in search box
    if (bookSearch) {
        bookSearch.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                currentPage = 1;
                loadBooks();
            }
        });
    }
    
    // Set up filter functionality
    if (categoryFilter) {
        categoryFilter.addEventListener('change', function() {
            currentPage = 1;
            loadBooks();
        });
    }
    
    if (availabilityFilter) {
        availabilityFilter.addEventListener('change', function() {
            currentPage = 1;
            loadBooks();
        });
    }
    
    // Function to load books from server
    function loadBooks() {
        showLoading(true);
        
        // Get filter values
        const category = categoryFilter ? categoryFilter.value : '';
        const availability = availabilityFilter ? availabilityFilter.value : '';
        const search = bookSearch ? bookSearch.value.trim() : '';
        
        // Fetch books from server
        fetch(`get_books.php?category=${category}&availability=${availability}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store all books for pagination
                    allBooks = data.books;
                    totalBooks = allBooks.length;
                    
                    // Display books
                    if (totalBooks > 0) {
                        displayBooks();
                        createPagination();
                        noBooks.style.display = 'none';
                    } else {
                        booksGrid.innerHTML = '';
                        pagination.innerHTML = '';
                        noBooks.style.display = 'block';
                    }
                } else {
                    console.error('Error loading books:', data.message);
                    booksGrid.innerHTML = `<div class="error-message">Failed to load books. Please try again.</div>`;
                    pagination.innerHTML = '';
                }
                showLoading(false);
            })
            .catch(error => {
                console.error('Error:', error);
                booksGrid.innerHTML = `<div class="error-message">Failed to connect to server. Please try again.</div>`;
                pagination.innerHTML = '';
                showLoading(false);
            });
    }
    
    // Function to display books with pagination - updated to remove icons from buttons
    function displayBooks() {
        // Calculate books for current page
        const startIndex = (currentPage - 1) * booksPerPage;
        const endIndex = Math.min(startIndex + booksPerPage, totalBooks);
        const booksToShow = allBooks.slice(startIndex, endIndex);
        
        // Clear current books
        booksGrid.innerHTML = '';
        
        // Display books
        booksToShow.forEach(book => {
            // Double-check availability - ensure UI is consistent regardless of database state
            const isBookAvailable = book.available_copies > 0 && book.status === 'available';
            
            // Create availability badge class based on status
            let statusClass, statusText;
            if (book.available_copies <= 0) {
                // Force unavailable status if no copies available
                statusClass = 'unavailable';
                statusText = 'Unavailable';
            } else {
                switch(book.status.toLowerCase()) {
                    case 'available':
                        statusClass = 'available';
                        statusText = 'Available';
                        break;
                    case 'reserved':
                        statusClass = 'reserved';
                        statusText = 'Reserved';
                        break;
                    case 'borrowed':
                        statusClass = 'borrowed';
                        statusText = 'Borrowed';
                        break;
                    default:
                        statusClass = 'unavailable';
                        statusText = 'Unavailable';
                }
            }
            
            // Create book card HTML
            const bookCard = document.createElement('div');
            bookCard.className = 'book-card';
            bookCard.innerHTML = `
                <div class="book-cover">
                    <img src="${book.cover_image}" alt="${book.title}">
                    <span class="book-status-badge ${statusClass}">${statusText}</span>
                </div>
                <div class="book-header">
                    <h3 class="book-title">${book.title}</h3>
                </div>
                <div class="book-body">
                    <div class="book-info">
                        <div class="info-item">
                            <span class="info-label">Author:</span> ${book.author}
                        </div>
                        <div class="info-item">
                            <span class="info-label">Category:</span> ${book.category_name || 'Uncategorized'}
                        </div>
                        <div class="info-item">
                            <span class="info-label">Copies:</span> 
                            <span class="available-count">${book.available_copies}</span>/${book.total_copies} available
                        </div>
                    </div>
                    <div class="book-actions">
                        <button class="book-action-btn view-btn" data-id="${book.book_id}">View</button>
                        <button class="book-action-btn reserve-btn" data-id="${book.book_id}" 
                            ${!isBookAvailable ? 'disabled' : ''}>
                            Reserve
                        </button>
                    </div>
                </div>
            `;
            
            booksGrid.appendChild(bookCard);
        });
        
        // Add event listeners to buttons
        addBookCardEventListeners();
    }
    
    // Function to create pagination controls
    function createPagination() {
        const totalPages = Math.ceil(totalBooks / booksPerPage);
        pagination.innerHTML = '';
        
        // Don't show pagination if only one page
        if (totalPages <= 1) {
            return;
        }
        
        // Previous button
        const prevBtn = document.createElement('button');
        prevBtn.className = 'page-btn prev';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevBtn.disabled = currentPage === 1;
        prevBtn.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                displayBooks();
                createPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        });
        pagination.appendChild(prevBtn);
        
        // Page number buttons (limited to 5 visible pages)
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(startPage + maxVisiblePages - 1, totalPages);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            // First page button
            const firstBtn = document.createElement('button');
            firstBtn.className = 'page-btn';
            firstBtn.textContent = '1';
            firstBtn.addEventListener('click', () => {
                currentPage = 1;
                displayBooks();
                createPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });
            pagination.appendChild(firstBtn);
            
            // Ellipsis if needed
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'page-ellipsis';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
        }
        
        // Page buttons
        for (let i = startPage; i <= endPage; i++) {
            const pageBtn = document.createElement('button');
            pageBtn.className = `page-btn ${i === currentPage ? 'active' : ''}`;
            pageBtn.textContent = i;
            pageBtn.addEventListener('click', () => {
                currentPage = i;
                displayBooks();
                createPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });
            pagination.appendChild(pageBtn);
        }
        
        if (endPage < totalPages) {
            // Ellipsis if needed
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'page-ellipsis';
                ellipsis.textContent = '...';
                pagination.appendChild(ellipsis);
            }
            
            // Last page button
            const lastBtn = document.createElement('button');
            lastBtn.className = 'page-btn';
            lastBtn.textContent = totalPages;
            lastBtn.addEventListener('click', () => {
                currentPage = totalPages;
                displayBooks();
                createPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });
            pagination.appendChild(lastBtn);
        }
        
        // Next button
        const nextBtn = document.createElement('button');
        nextBtn.className = 'page-btn next';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextBtn.disabled = currentPage === totalPages;
        nextBtn.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                displayBooks();
                createPagination();
                window.scrollTo({top: 0, behavior: 'smooth'});
            }
        });
        pagination.appendChild(nextBtn);
    }
    
    // Function to add event listeners to book card buttons
    function addBookCardEventListeners() {
        // View Details button
        document.querySelectorAll('.view-btn').forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-id');
                showBookDetails(bookId);
            });
        });
        
        // Reserve button
        document.querySelectorAll('.reserve-btn').forEach(button => {
            if (!button.disabled) {
                button.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-id');
                    showReservationConfirmation(bookId);
                });
            }
        });
    }
    
    // Function to show book details modal
    function showBookDetails(bookId) {
        const book = allBooks.find(b => b.book_id == bookId);
        
        if (book) {
            // Double-check availability
            const isBookAvailable = book.available_copies > 0 && book.status === 'available';
            
            // Determine status text and class
            let statusClass = book.status;
            let statusText = book.status.charAt(0).toUpperCase() + book.status.slice(1);
            
            // Force unavailable if no copies
            if (book.available_copies <= 0) {
                statusClass = 'unavailable';
                statusText = 'Unavailable';
            }
            
            const detailContent = document.getElementById('book-detail-content');
            
            // Create modal content
            detailContent.innerHTML = `
                <div class="modal-body">
                    <div class="book-detail-cover">
                        <img src="${book.cover_image}" alt="${book.title}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="book-details">
                        <h2>${book.title}</h2>
                        <p class="book-author">by ${book.author}</p>
                        <p class="book-category">Category: ${book.category_name || 'Uncategorized'}</p>
                        <p class="book-isbn">ISBN: ${book.isbn || 'N/A'}</p>
                        <p class="book-description">${book.summary || 'No description available.'}</p>
                        <div class="book-availability">
                            <p><span class="status-text ${statusClass}">${statusText}</span></p>
                            <p>${book.available_copies} of ${book.total_copies} copies available</p>
                        </div>
                        ${isBookAvailable ? 
                            `<button class="reserve-now-btn" data-id="${book.book_id}">Reserve Now</button>` : 
                            '<p>This book is currently not available for reservation.</p>'}
                    </div>
                </div>
            `;
            
            // Add event listener to the Reserve Now button if it exists
            const reserveNowBtn = detailContent.querySelector('.reserve-now-btn');
            if (reserveNowBtn) {
                reserveNowBtn.addEventListener('click', function() {
                    const bookId = this.getAttribute('data-id');
                    bookModal.style.display = 'none';
                    showReservationConfirmation(bookId);
                });
            }
            
            // Show the modal
            bookModal.style.display = 'block';
        } else {
            console.error('Book not found:', bookId);
        }
    }
    
    // Function to show reservation confirmation
    function showReservationConfirmation(bookId) {
        const book = allBooks.find(b => b.book_id == bookId);
        
        if (book) {
            document.getElementById('confirmation-message').innerHTML = 
                `Are you sure you want to reserve <strong>${book.title}</strong>?`;
                
            // Get the confirm button and add event listener
            const confirmBtn = document.getElementById('confirm-reservation');
            
            // Remove any existing event listeners (to prevent multiple reservations)
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            // Add new event listener
            newConfirmBtn.addEventListener('click', function() {
                // Show loading state
                newConfirmBtn.disabled = true;
                newConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                
                // Send a reservation request to the server
                fetch('reserve_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ book_id: bookId })
                })
                .then(response => response.json())
                .then(data => {
                    confirmationModal.style.display = 'none';
                    
                    if (data.success) {
                        // Show success message
                        showNotification('success', `${data.book_title} has been reserved successfully! You have 3 days to pick up your book from the library.`);
                        
                        // Refresh book list to update availability
                        loadBooks();
                        
                        // Refresh user's reservations if they're displayed on the page
                        if (typeof loadUserReservations === 'function') {
                            loadUserReservations();
                        }
                    } else {
                        // Show error message
                        showNotification('error', data.message || 'Failed to reserve book. Please try again.');
                        
                        // If it's a duplicate reservation, reload to reflect the current status
                        if (data.message && data.message.includes("You already have this book")) {
                            loadBooks();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error reserving book:', error);
                    showNotification('error', 'Failed to reserve book. Please try again.');
                });
            });
            
            // Same for cancel button
            const cancelBtn = document.getElementById('cancel-reservation');
            const newCancelBtn = cancelBtn.cloneNode(true);
            cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
            
            newCancelBtn.addEventListener('click', function() {
                confirmationModal.style.display = 'none';
            });
            
            // Show modal
            confirmationModal.style.display = 'block';
        }
    }
    
    // Add this function near the top of your JavaScript file:
    function showNotification(type, message) {
        // Remove any existing notifications
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="close-notification"><i class="fas fa-times"></i></button>
        `;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show with animation
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Add close button functionality
        const closeBtn = notification.querySelector('.close-notification');
        closeBtn.addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }
    
    // Helper function to toggle loading spinner
    function showLoading(show) {
        if (show) {
            loadingSpinner.style.display = 'flex';
            booksGrid.style.display = 'none';
        } else {
            loadingSpinner.style.display = 'none';
            booksGrid.style.display = 'grid';
        }
    }
    
    // Load books on page load
    loadBooks();
});
