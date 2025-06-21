// homepage.js
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
    
    // User preferences functionality
    const preferenceCheckboxes = document.querySelectorAll('.preference-checkbox');
    const savePreferencesBtn = document.getElementById('save-preferences');
    const selectedCountElem = document.getElementById('selected-count');
    const preferencesMessage = document.getElementById('preferences-message');
    const maxSelections = 3;
    
    // Initialize selected count
    let selectedCount = document.querySelectorAll('.preference-checkbox:checked').length;
    selectedCountElem.textContent = selectedCount;
    
    // Handle checkbox changes
    preferenceCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const checkedBoxes = document.querySelectorAll('.preference-checkbox:checked');
            
            if (checkedBoxes.length > maxSelections) {
                this.checked = false;
                showMessage('You can only select up to 3 categories.', 'error');
                return;
            }
            
            selectedCount = checkedBoxes.length;
            selectedCountElem.textContent = selectedCount;
        });
    });
    
    // Save preferences
    if (savePreferencesBtn) {
        savePreferencesBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.preference-checkbox:checked');
            const selectedCategories = Array.from(checkedBoxes).map(box => parseInt(box.value));
            
            fetch('save_preferences.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    categories: selectedCategories
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Your preferences have been saved successfully!', 'success');
                } else {
                    showMessage('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while saving your preferences.', 'error');
            });
        });
    }
    
    function showMessage(message, type) {
        preferencesMessage.textContent = message;
        preferencesMessage.className = 'preferences-message ' + type;
        preferencesMessage.style.display = 'block';
        
        setTimeout(() => {
            preferencesMessage.style.opacity = '0';
            setTimeout(() => {
                preferencesMessage.style.display = 'none';
                preferencesMessage.style.opacity = '1';
            }, 500);
        }, 3000);
    }
    
    // Reserve button functionality
    const reserveButtons = document.querySelectorAll('.reserve-btn');
    
    reserveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookId = this.dataset.bookId;
            const bookTitle = this.parentElement.querySelector('h3').textContent;
            
            // Confirm reservation
            if (confirm(`Do you want to reserve "${bookTitle}"?`)) {
                // Make API call to reserve_book.php
                fetch('reserve_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id: bookId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Book reserved successfully! Please pick it up within 3 days once ready.');
                        // Optionally refresh the page or update UI
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request.');
                });
            }
        });
    });
    
    // Reservation cancel confirmation
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to cancel this reservation?')) {
                e.preventDefault();
            }
        });
    });
    
    // Add animations for stats cards
    const statCards = document.querySelectorAll('.stat-card');
    
    const observerOptions = {
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease, transform 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });
    
    // Add automatic fade-out for success/error messages
    const messages = document.querySelectorAll('.success-message, .error-message');
    
    if (messages.length > 0) {
        setTimeout(() => {
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transform = 'translateY(-10px)';
                msg.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                
                setTimeout(() => {
                    msg.remove();
                }, 500);
            });
        }, 5000);
    }
});
