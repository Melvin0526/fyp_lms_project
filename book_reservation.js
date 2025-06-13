// JavaScript for the book reservation system
// Handles search, filters, modals, and reservation actions
document.addEventListener('DOMContentLoaded', function() {    // Elements
    const bookModal = document.getElementById('book-modal');
    const confirmationModal = document.getElementById('confirmation-modal');
    const closeButtons = document.querySelectorAll('.close-modal');
    const bookSearch = document.getElementById('book-search');
    const searchBtn = document.getElementById('search-btn');
    
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
    
    // Search functionality
    // To be implemented when connected to backend
    searchBtn.addEventListener('click', function() {
        const searchTerm = bookSearch.value.trim().toLowerCase();
        
        if (searchTerm === '') {
            return;
        }
        
        // Alert for demonstration purposes
        alert(`Search functionality will be implemented in future updates. Search term: ${searchTerm}`);
    });
    
    // Handle enter key in search box
    bookSearch.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            searchBtn.click();
        }
    });
    
});
