/**
 * Admin Physical Book Checkout Functionality
 * Handles autocomplete for users and books and form interactions
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get the input elements
    const userInput = document.getElementById('user_identifier');
    const bookInput = document.getElementById('book_identifier');
    const userSuggestions = document.getElementById('user-suggestions');
    const bookSuggestions = document.getElementById('book-suggestions');
    
    // Initialize the autocomplete functionality if elements exist
    if (userInput && userSuggestions) {
        initAutocomplete(userInput, userSuggestions, 'user');
    }
    
    if (bookInput && bookSuggestions) {
        initAutocomplete(bookInput, bookSuggestions, 'book');
    }
    
    // Hide notification messages after 5 seconds
    const notificationMessage = document.querySelector('.checkout-message');
    if (notificationMessage) {
        setTimeout(() => {
            notificationMessage.classList.add('fadeOut');
            setTimeout(() => {
                notificationMessage.style.display = 'none';
            }, 300);
        }, 5000);
    }
    
    /**
     * Initialize autocomplete functionality for an input field
     * @param {HTMLElement} inputElement - The input field
     * @param {HTMLElement} suggestionsElement - Container for suggestions
     * @param {string} type - Type of autocomplete ('user' or 'book')
     */
    function initAutocomplete(inputElement, suggestionsElement, type) {
        // Add input event listener with debounce
        inputElement.addEventListener('input', debounce(function() {
            const query = inputElement.value.trim();
            
            // Don't search for short queries
            if (query.length < 2) {
                suggestionsElement.style.display = 'none';
                return;
            }
            
            // Fetch suggestions from server
            fetchSuggestions(query, type, suggestionsElement);
        }, 300));
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== inputElement && !suggestionsElement.contains(e.target)) {
                suggestionsElement.style.display = 'none';
            }
        });
        
        // Show suggestions on input focus if there's content
        inputElement.addEventListener('focus', function() {
            if (inputElement.value.trim().length >= 2) {
                fetchSuggestions(inputElement.value.trim(), type, suggestionsElement);
            }
        });
        
        // Handle keyboard navigation
        inputElement.addEventListener('keydown', function(e) {
            if (suggestionsElement.style.display !== 'block') return;
            
            const suggestions = suggestionsElement.querySelectorAll('.autocomplete-suggestion');
            if (suggestions.length === 0) return;
            
            let activeIndex = -1;
            for (let i = 0; i < suggestions.length; i++) {
                if (suggestions[i].classList.contains('active')) {
                    activeIndex = i;
                    break;
                }
            }
            
            // Arrow down
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (activeIndex < suggestions.length - 1) {
                    if (activeIndex >= 0) suggestions[activeIndex].classList.remove('active');
                    suggestions[activeIndex + 1].classList.add('active');
                    suggestions[activeIndex + 1].scrollIntoView({ block: 'nearest' });
                }
            } 
            // Arrow up
            else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (activeIndex > 0) {
                    suggestions[activeIndex].classList.remove('active');
                    suggestions[activeIndex - 1].classList.add('active');
                    suggestions[activeIndex - 1].scrollIntoView({ block: 'nearest' });
                }
            }
            // Enter key - select active suggestion
            else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                suggestions[activeIndex].click();
            }
            // Escape key - close suggestions
            else if (e.key === 'Escape') {
                suggestionsElement.style.display = 'none';
            }
        });
    }
    
    /**
     * Fetch suggestions from server
     * @param {string} query - Search query
     * @param {string} type - Type of search ('user' or 'book')
     * @param {HTMLElement} suggestionsElement - Container for displaying suggestions
     */
    function fetchSuggestions(query, type, suggestionsElement) {
        // Create the URL with query parameters
        const url = `admin_autocomplete.php?type=${type}&query=${encodeURIComponent(query)}`;
        
        // Fetch data from server
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                displaySuggestions(data, suggestionsElement, type);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    }
    
    /**
     * Display suggestions in the suggestions element
     * @param {Array} suggestions - Array of suggestion objects
     * @param {HTMLElement} suggestionsElement - Container for displaying suggestions
     * @param {string} type - Type of suggestions ('user' or 'book')
     */
    function displaySuggestions(suggestions, suggestionsElement, type) {
        // Clear previous suggestions
        suggestionsElement.innerHTML = '';
        
        // If no suggestions, hide the element
        if (!suggestions || suggestions.length === 0) {
            suggestionsElement.style.display = 'none';
            return;
        }
        
        // Create a document fragment for better performance
        const fragment = document.createDocumentFragment();
        
        // Create suggestion elements
        suggestions.forEach(suggestion => {
            const div = document.createElement('div');
            div.className = 'autocomplete-suggestion';
            
            if (type === 'book' && !suggestion.available) {
                div.classList.add('unavailable');
            }
            
            div.textContent = suggestion.label;
            
            // Add click event to select this suggestion
            div.addEventListener('click', function() {
                if (type === 'user') {
                    document.getElementById('user_identifier').value = suggestion.value;
                } else {
                    document.getElementById('book_identifier').value = suggestion.value;
                }
                suggestionsElement.style.display = 'none';
            });
            
            fragment.appendChild(div);
        });
        
        // Add suggestions to the DOM
        suggestionsElement.appendChild(fragment);
        suggestionsElement.style.display = 'block';
    }
    
    // Form validation
    const checkoutForm = document.querySelector('.checkout-form form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const userIdentifier = document.getElementById('user_identifier').value.trim();
            const bookIdentifier = document.getElementById('book_identifier').value.trim();
            
            // Basic validation
            if (userIdentifier === '' || bookIdentifier === '') {
                e.preventDefault();
                
                // Show error message
                let errorMessage = document.querySelector('.checkout-message');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.className = 'checkout-message error';
                    checkoutForm.appendChild(errorMessage);
                } else {
                    errorMessage.className = 'checkout-message error';
                }
                
                errorMessage.textContent = 'Please fill in all required fields.';
                errorMessage.style.display = 'block';
                
                // Hide message after 5 seconds
                setTimeout(() => {
                    errorMessage.classList.add('fadeOut');
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }
        });
    }
});

/**
 * Debounce function to prevent too many requests
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}