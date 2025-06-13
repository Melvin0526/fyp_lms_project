document.addEventListener("DOMContentLoaded", function() {
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
    
    // Fade out notifications after 5 seconds
    const notifications = document.querySelectorAll('.success-message, .error-message');
    if (notifications.length > 0) {
        setTimeout(() => {
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateY(-10px)';
                notification.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    notification.remove();
                }, 500);
            });
        }, 5000);
    }
    
    // Handle review button clicks
    const reviewButtons = document.querySelectorAll('.review-btn');
    reviewButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Room review feature will be available soon!');
        });
    });
    
    // Table row hover animation
    const tableRows = document.querySelectorAll('.reservations-table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transition = 'background-color 0.3s ease';
        });
    });
    
    // Add active class to current filter button
    const currentUrl = window.location.href;
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        const buttonUrl = button.getAttribute('href');
        if (currentUrl.includes(buttonUrl)) {
            button.classList.add('active');
        }
    });
});