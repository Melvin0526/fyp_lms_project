document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const gridViewBtn = document.querySelector('.grid-view');
    const listViewBtn = document.querySelector('.list-view');
    const bookGrid = document.querySelector('.book-grid');
    const bookTable = document.querySelector('.book-table');
    
    if (gridViewBtn && listViewBtn && bookGrid && bookTable) {
        gridViewBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                this.classList.add('active');
                listViewBtn.classList.remove('active');
                bookGrid.style.display = 'grid';
                bookTable.style.display = 'none';
            }
        });
        
        listViewBtn.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                this.classList.add('active');
                gridViewBtn.classList.remove('active');
                bookGrid.style.display = 'none';
                bookTable.style.display = 'table';
            }
        });
    }
    
    // Book action buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const bookId = this.getAttribute('data-id');
            alert(`View book details for ID: ${bookId}`);
        });
    });
    
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const bookId = this.getAttribute('data-id');
            alert(`Edit book with ID: ${bookId}`);
        });
    });
    
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const bookId = this.getAttribute('data-id');
            const bookCard = this.closest('.book-card') || this.closest('tr');
            
            let bookTitle = '';
            if (this.closest('.book-card')) {
                bookTitle = bookCard.querySelector('.book-title').textContent;
            } else {
                bookTitle = bookCard.cells[1].textContent;
            }
            
            if (confirm(`Are you sure you want to delete "${bookTitle}" (ID: ${bookId})?`)) {
                alert(`Book "${bookTitle}" would be deleted in a real application`);
            }
        });
    });
    
    // Add new book button
    const addBookBtn = document.querySelector('.book-create-btn');
    if (addBookBtn) {
        addBookBtn.addEventListener('click', function() {
            alert('Add new book form will appear here');
        });
    }
    
    // Filters functionality
    const categoryFilter = document.getElementById('category-filter');
    const availabilityFilter = document.getElementById('availability-filter');
    
    if (categoryFilter && availabilityFilter) {
        categoryFilter.addEventListener('change', function() {
            filterBooks();
        });
        
        availabilityFilter.addEventListener('change', function() {
            filterBooks();
        });
    }
    
    function filterBooks() {
        if (!categoryFilter || !availabilityFilter) return;
        
        const category = categoryFilter.value;
        const availability = availabilityFilter.value;
        
        console.log(`Filtering books by category: ${category}, availability: ${availability}`);
        // In a real application, you would filter the books based on the selected values
        // This could involve an AJAX request to the server or filtering client-side
        
        // For demo, just show an alert
        alert(`Filter applied - Category: ${category}, Availability: ${availability}`);
    }
    
    // Pagination
    const pageButtons = document.querySelectorAll('.page-btn');
    pageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                document.querySelector('.page-btn.active').classList.remove('active');
                this.classList.add('active');
                // In a real application, you would fetch the books for the selected page
            }
        });
    });
});
