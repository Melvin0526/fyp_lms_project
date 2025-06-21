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
    
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to current button and corresponding pane
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Get elements
    const confirmationModal = document.getElementById('confirmation-modal');
    const closeModalBtn = document.querySelector('.close-modal');
    const cancelBtn = document.getElementById('cancel-btn');
    const confirmBtn = document.getElementById('confirm-btn');
    const notificationToast = document.getElementById('notification-toast');
    const toastMessage = document.querySelector('.toast-message');
    const toastCloseBtn = document.querySelector('.toast-close');
    
    // Initialize page by loading current reservations
    loadCurrentReservations();
    
    // Also load borrow history for the Past tab (even though it's not visible yet)
    loadBorrowHistory();
    
    // Function to load current reservations
    function loadCurrentReservations() {
        const activeContainer = document.getElementById('current-tab');
        if (!activeContainer) return;
        
        // Show loading state
        activeContainer.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading your current reservations...</p>
            </div>
        `;
        
        // Fetch current reservations
        fetch('my_reservations.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.reservations.length > 0) {
                        activeContainer.innerHTML = `
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-bookmark"></i> Current Reservations & Loans</h3>
                                    <div class="reservation-limit">
                                        <span>${data.active_count}</span>/<span>${data.max_allowed}</span> active reservations
                                    </div>
                                </div>
                                <div id="reservations-list" class="reservations-list">
                                    <!-- Reservations will be loaded here -->
                                </div>
                            </div>
                        `;
                        displayReservations(data.reservations);
                    } else {
                        // Empty state for current reservations with browse button
                        activeContainer.innerHTML = `
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-bookmark"></i> Current Reservations & Loans</h3>
                                    <div class="reservation-limit">
                                        <span>0</span>/<span>${data.max_allowed}</span> active reservations
                                    </div>
                                </div>
                                <div class="empty-state">
                                    <i class="fas fa-book"></i>
                                    <h4>No Active Reservations</h4>
                                    <p>You don't have any active reservations or loans.</p>
                                    <a href="book_reservation.php" class="action-btn primary-btn">Browse Books</a>
                                </div>
                            </div>
                        `;
                        // Make the empty state visible
                        const emptyState = activeContainer.querySelector('.empty-state');
                        if (emptyState) emptyState.style.display = 'flex';
                    }
                } else {
                    activeContainer.innerHTML = `
                        <div class="error-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <h4>Oops! Something went wrong</h4>
                            <p>${data.message || 'Unable to load reservations at this time.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading reservations:', error);
                activeContainer.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <h4>Error Loading Reservations</h4>
                        <p>Please try again later or contact support.</p>
                    </div>
                `;
            });
    }
    
    // Function to display reservations
    function displayReservations(reservations) {
        const reservationsContainer = document.getElementById('reservations-list');
        if (!reservationsContainer) return;
        
        reservationsContainer.innerHTML = '';
        
        reservations.forEach(reservation => {
            // Format dates
            const reservedDate = formatDate(reservation.reserved_at);
            const dueDate = reservation.due_date ? formatDate(reservation.due_date) : 'Not set';
            
            // Check if book is overdue
            const isOverdue = reservation.status === 'borrowed' && reservation.due_date && 
                             (new Date(reservation.due_date) < new Date());
            
            // Set status badge class and text
            let statusClass = '';
            let statusText = '';
            
            switch(reservation.status) {
                case 'reserved':
                    statusClass = 'reserved';
                    statusText = 'Reserved';
                    break;
                case 'ready_for_pickup':
                    statusClass = 'ready';
                    statusText = 'Ready for Pickup';
                    break;
                case 'borrowed':
                    if (isOverdue) {
                        statusClass = 'overdue';
                        statusText = 'Overdue';
                    } else {
                        statusClass = 'borrowed';
                        statusText = 'Borrowed';
                    }
                    break;
            }
            
            // Calculate days overdue or days remaining
            let daysText = '';
            if (isOverdue && reservation.due_date) {
                const dueDate = new Date(reservation.due_date);
                const today = new Date();
                // Reset time components for accurate date comparison
                dueDate.setHours(0, 0, 0, 0);
                today.setHours(0, 0, 0, 0);
                
                const daysOverdue = Math.floor((today - dueDate) / (1000 * 60 * 60 * 24));
                daysText = `Overdue by ${daysOverdue} day${daysOverdue !== 1 ? 's' : ''}`;
            } else {
                daysText = reservation.days_remaining;
            }
            
            // Create reservation card
            const card = document.createElement('div');
            card.className = 'reservation-card';
            if (isOverdue) {
                card.classList.add('overdue-card');
            }
            
            card.innerHTML = `
                <div class="book-cover">
                    <img src="${reservation.cover_image || 'img/default-book-cover.png'}" alt="${reservation.title}">
                </div>
                <div class="book-info">
                    <h4 class="book-title">${reservation.title}</h4>
                    <p class="book-author">${reservation.author || 'Unknown Author'}</p>
                    <div class="status-info">
                        <span class="status-badge ${statusClass}">${statusText}</span>
                        <span class="days-remaining ${isOverdue ? 'overdue-text' : ''}">${daysText}</span>
                    </div>
                    <div class="due-date-info">
                        <span class="due-date-label">Due Date:</span>
                        <span class="due-date-value ${isOverdue ? 'overdue-text' : ''}">${dueDate}</span>
                    </div>
                    ${reservation.status !== 'borrowed' ? 
                        `<button class="action-btn cancel-btn" data-id="${reservation.loan_id}" data-title="${reservation.title}">
                            Cancel
                        </button>` : ''
                    }
                </div>
            `;
            
            // Add event listener to cancel button
            const cancelBtn = card.querySelector('.cancel-btn');
            if (cancelBtn) {
                cancelBtn.addEventListener('click', function() {
                    const loanId = this.getAttribute('data-id');
                    const bookTitle = this.getAttribute('data-title');
                    showCancelConfirmation(loanId, bookTitle);
                });
            }
            
            reservationsContainer.appendChild(card);
        });
    }
    
    // Function to show cancel confirmation
    function showCancelConfirmation(loanId, bookTitle) {
        if (!confirmationModal) return;
        
        confirmationModal.querySelector('.modal-content').innerHTML = `
            <h3>Cancel Reservation</h3>
            <p>Are you sure you want to cancel your reservation for <strong>${bookTitle}</strong>?</p>
            <div class="modal-buttons">
                <button id="cancel-btn" class="action-btn">No, Keep It</button>
                <button id="confirm-btn" class="action-btn primary-btn">Yes, Cancel Reservation</button>
            </div>
            <button class="close-modal">&times;</button>
        `;
        
        // Show modal
        confirmationModal.classList.add('show');
        
        // Add event listeners to new buttons
        const newCancelBtn = confirmationModal.querySelector('#cancel-btn');
        const newConfirmBtn = confirmationModal.querySelector('#confirm-btn');
        const newCloseBtn = confirmationModal.querySelector('.close-modal');
        
        if (newCancelBtn) {
            newCancelBtn.addEventListener('click', () => {
                confirmationModal.classList.remove('show');
            });
        }
        
        if (newConfirmBtn) {
            newConfirmBtn.addEventListener('click', () => {
                cancelReservation(loanId, bookTitle);
                confirmationModal.classList.remove('show');
            });
        }
        
        if (newCloseBtn) {
            newCloseBtn.addEventListener('click', () => {
                confirmationModal.classList.remove('show');
            });
        }
    }
    
    // Function to cancel reservation
    function cancelReservation(loanId, bookTitle) {
        fetch('cancel_reservation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                loan_id: loanId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showNotification(`Reservation for "${bookTitle}" has been cancelled.`, 'success');
                
                // Reload reservations
                loadCurrentReservations();
            } else {
                // Show error message
                showNotification(data.message || 'Failed to cancel reservation. Please try again.', 'error');
            }
        })
        .catch(error => {
            console.error('Error cancelling reservation:', error);
            showNotification('An error occurred. Please try again later.', 'error');
        });
    }
    
    // Function to load borrowing history
    function loadBorrowHistory() {
        const historyContainer = document.getElementById('past-tab');
        if (!historyContainer) return;
        
        // Show loading state
        historyContainer.innerHTML = `
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <p>Loading your borrowing history...</p>
            </div>
        `;
        
        // Fetch borrowing history
        fetch('get_borrow_history.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.history && data.history.length > 0) {
                        // Display history if available
                        displayBorrowHistory(data.history);
                    } else {
                        // Empty state for borrowing history with browse button
                        historyContainer.innerHTML = `
                            <div class="card">
                                <div class="card-header">
                                    <h3><i class="fas fa-history"></i> Past Borrowings</h3>
                                </div>
                                <div class="empty-state">
                                    <i class="fas fa-book"></i>
                                    <h4>No Borrowing History</h4>
                                    <p>You haven't borrowed any books yet.</p>
                                    <a href="book_reservation.php" class="action-btn primary-btn">Browse Books</a>
                                </div>
                            </div>
                        `;
                        // Make the empty state visible
                        const emptyState = historyContainer.querySelector('.empty-state');
                        if (emptyState) emptyState.style.display = 'flex';
                    }
                } else {
                    historyContainer.innerHTML = `
                        <div class="error-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <h4>Oops! Something went wrong</h4>
                            <p>${data.message || 'Unable to load borrowing history at this time.'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading borrowing history:', error);
                historyContainer.innerHTML = `
                    <div class="error-state">
                        <i class="fas fa-exclamation-circle"></i>
                        <h4>Error Loading Borrowing History</h4>
                        <p>Please try again later or contact support.</p>
                    </div>
                `;
            });
    }
    
    // Function to display borrowing history
    function displayBorrowHistory(historyItems) {
        console.log('Displaying history items:', historyItems.length); // Debug log
        
        const historyContainer = document.getElementById('past-tab');
        if (!historyContainer) {
            console.error('History container not found');
            return;
        }
        
        // Create the table structure with the columns you requested
        historyContainer.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Past Borrowings</h3>
                </div>
                <div class="history-list">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Book</th>
                                <th>Reserved On</th>
                                <th>Pick Up Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="history-table-body">
                        </tbody>
                    </table>
                </div>
            </div>
        `;
        
        const tableBody = document.getElementById('history-table-body');
        
        // Add each history item to the table
        historyItems.forEach(item => {
            const reservedDate = formatDate(item.reserved_at);
            const pickupDate = item.picked_up_at ? formatDate(item.picked_up_at) : 'N/A';
            const returnDate = item.return_date ? formatDate(item.return_date) : 'N/A';
            
            // Check if book is/was overdue (include a check for items that were overdue but now returned)
            const isOverdue = (item.status === 'borrowed' && item.due_date && new Date(item.due_date) < new Date()) || 
                              (item.was_overdue === true || item.was_overdue === 1);
            
            // Determine status display
            let statusClass = '';
            let statusText = '';
            
            switch(item.status) {
                case 'returned':
                    statusClass = item.was_overdue ? 'returned-late' : 'returned';
                    statusText = item.was_overdue ? 'Returned Late' : 'Returned';
                    break;
                case 'borrowed':
                    statusClass = isOverdue ? 'overdue' : 'borrowed';
                    statusText = isOverdue ? 'Overdue' : 'Borrowed';
                    break;
                case 'cancelled':
                    statusClass = 'cancelled';
                    statusText = 'Cancelled';
                    break;
                case 'expired':
                    statusClass = 'expired';
                    statusText = 'Expired';
                    break;
                default:
                    statusClass = item.status;
                    statusText = item.status.charAt(0).toUpperCase() + item.status.slice(1);
            }
            
            // Create table row
            const row = document.createElement('tr');
            if (isOverdue && item.status === 'borrowed') {
                row.classList.add('overdue-row');
            }
            
            row.innerHTML = `
                <td data-label="Book">
                    <div class="book-info">
                        <img src="${item.cover_image || 'images/placeholder-cover.png'}" 
                             class="book-thumbnail" alt="Book Cover">
                        <div class="book-details">
                            <div class="book-title">${item.title}</div>
                            <div class="book-author">${item.author || 'Unknown Author'}</div>
                            <div class="book-category">${item.category_name || 'Uncategorized'}</div>
                        </div>
                    </div>
                </td>
                <td data-label="Reserved On">${reservedDate}</td>
                <td data-label="Pick Up Date">${pickupDate}</td>
                <td data-label="Return Date">${returnDate}</td>
                <td data-label="Status"><span class="status-badge ${statusClass}">${statusText}</span></td>
            `;
            
            tableBody.appendChild(row);
        });
    }
    
    // Function to format dates
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }
    
    // Function to show notification
    function showNotification(message, type = 'success') {
        if (!notificationToast) return;
        
        // Set message and type
        toastMessage.textContent = message;
        notificationToast.className = `notification-toast toast-${type}`;
        
        // Show toast
        notificationToast.classList.add('show');
        
        // Hide after 5 seconds
        setTimeout(() => {
            notificationToast.classList.remove('show');
        }, 5000);
    }
    
    // Close modal when clicking X or Cancel button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            confirmationModal.classList.remove('show');
        });
    }
    
    // Close notification toast
    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', () => {
            notificationToast.classList.remove('show');
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            confirmationModal.classList.remove('show');
        }
    });
});
