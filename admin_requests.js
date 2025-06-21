document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
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
            
            // Populate the tab content if it's empty (for tabs that aren't "all-requests")
            if (tabId !== 'all-requests') {
                populateTabContent(tabId);
            }
        });
    });
    
    // Action buttons functionality - update to use AJAX
    function setupActionButtons() {
        // Approve reservation button
        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                if (confirm(`Are you sure you want to approve this reservation?`)) {
                    updateLoanStatus(requestId, 'ready_for_pickup');
                }
            });
        });
        
        // Reject button
        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                if (confirm(`Are you sure you want to reject request ID ${requestId}?`)) {
                    updateLoanStatus(requestId, 'cancelled');
                }
            });
        });
        
        // Mark ready button
        document.querySelectorAll('.mark-ready-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                updateLoanStatus(requestId, 'ready_for_pickup');
            });
        });
        
        // Complete button
        document.querySelectorAll('.complete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                updateLoanStatus(requestId, 'borrowed');
            });
        });
        
        // Return button
        document.querySelectorAll('.return-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const requestId = this.getAttribute('data-id');
                if (confirm(`Are you sure you want to mark this book as returned?`)) {
                    updateLoanStatus(requestId, 'returned');
                }
            });
        });
    }
    
    // Function to update loan status via AJAX - remove success message
    function updateLoanStatus(loanId, newStatus) {
        // Show loading state for the specific row
        const row = document.querySelector(`tr[data-loan-id="${loanId}"]`);
        if (row) {
            row.classList.add('updating');
        }
        
        fetch('update_loan_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                loan_id: loanId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            // Remove loading state
            if (row) {
                row.classList.remove('updating');
            }
            
            if (data.success) {
                // Refresh the data without showing success message
                loadRequests();
            } else {
                // Still show error messages
                showNotification('error', data.message || 'Failed to update request status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (row) {
                row.classList.remove('updating');
            }
            showNotification('error', 'An unexpected error occurred');
        });
    }
    
    // Function to handle book return - remove success message
    function returnBook(loanId) {
        const row = document.querySelector(`tr[data-loan-id="${loanId}"]`);
        if (row) {
            row.classList.add('updating');
        }
        
        fetch('return_book.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                loan_id: loanId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (row) {
                row.classList.remove('updating');
            }
            
            if (data.success) {
                // Refresh the data without showing success message
                loadRequests();
            } else {
                // Still show error messages
                showNotification('error', data.message || 'Failed to process book return');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (row) {
                row.classList.remove('updating');
            }
            showNotification('error', 'An unexpected error occurred');
        });
    }
    
    // Update the formatStatus function
    function formatStatus(status) {
        // Check if book is overdue (borrowed but past due date)
        if (status === 'borrowed' && arguments.length > 1 && arguments[1]) {
            const dueDate = new Date(arguments[1]);
            const today = new Date();
            
            // Reset time components for accurate date comparison
            dueDate.setHours(0, 0, 0, 0);
            today.setHours(0, 0, 0, 0);
            
            if (dueDate < today) {
                return 'Overdue';
            }
        }
        
        // Check if book was returned late
        if (status === 'returned' && arguments.length > 2 && arguments[1] && arguments[2]) {
            const dueDate = new Date(arguments[1]);
            const returnDate = new Date(arguments[2]);
            
            // Reset time components for accurate date comparison
            dueDate.setHours(0, 0, 0, 0);
            returnDate.setHours(0, 0, 0, 0);
            
            if (returnDate > dueDate) {
                return 'Returned Late';
            }
        }
        
        switch(status) {
            case 'ready_for_pickup':
                return 'Ready for Pickup';
            case 'borrowed':
                return 'Borrowed';
            case 'returned':
                return 'Returned';
            case 'cancelled':
                return 'Cancelled';
            case 'expired':
                return 'Expired';
            default:
                return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }
    
    // Add a helper function to check if a book was returned late
    function wasReturnedLate(dueDate, returnDate) {
        if (!dueDate || !returnDate) return false;
        
        const dueDateObj = new Date(dueDate);
        const returnDateObj = new Date(returnDate);
        
        // Reset time components for accurate date comparison
        dueDateObj.setHours(0, 0, 0, 0);
        returnDateObj.setHours(0, 0, 0, 0);
        
        return returnDateObj > dueDateObj;
    }
    
    // Function to show notification
    function showNotification(type, message) {
        // You would implement this based on your UI
        alert(`${type.toUpperCase()}: ${message}`);
    }
    
    // Function to load request details
    function loadRequestDetails(requestId) {
        fetch(`get_loan_details.php?id=${requestId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showNotification('error', data.error);
                    return;
                }
                
                // Display details in a modal or panel
                showRequestDetailsModal(data);
            })
            .catch(error => {
                console.error('Error loading request details:', error);
                showNotification('error', 'Failed to load request details');
            });
    }
    
    // Function to show request details modal
    function showRequestDetailsModal(data) {
        // Create or update modal with request details
        // This would be implemented based on your UI design
        alert(`Request details for ID ${data.loan_id}:\nBook: ${data.book_title}\nStatus: ${data.status}\nUser: ${data.username}`);
    }
    
    // Replace the loadRequests function with this clean version
    function loadRequests() {
        const allRequestsTab = document.getElementById('all-requests');
        
        // Show loading indicator
        allRequestsTab.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><p>Loading requests...</p></div>';
        
        // Get filter values
        const statusFilter = document.getElementById('status-filter')?.value || '';
        const typeFilter = document.getElementById('type-filter')?.value || '';
        const dateFilter = document.getElementById('date-filter')?.value || '';
        
        // Build query string
        let queryString = '';
        if (statusFilter) queryString += `&status=${statusFilter}`;
        if (typeFilter) queryString += `&type=${typeFilter}`;
        if (dateFilter) queryString += `&date=${dateFilter}`;
        
        // Remove first & if present
        if (queryString.startsWith('&')) {
            queryString = queryString.substring(1);
        }
        
        // Fetch requests from server
        fetch(`get_loan_requests.php${queryString ? '?' + queryString : ''}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderRequestsTable(data.requests, allRequestsTab);
                } else {
                    allRequestsTab.innerHTML = `<div class="error-message">${data.message || 'Failed to load requests'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading requests:', error);
                allRequestsTab.innerHTML = '<div class="error-message">Failed to connect to server. Please try again.</div>';
            });
    }
    
    // Update the table columns in renderRequestsTable function
    function renderRequestsTable(requests, container) {
        // Create table
        const table = document.createElement('table');
        table.className = 'request-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Book Title</th>
                    <th>User</th>
                    <th>Request Date</th>
                    <th>Pickup Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Will be populated with requests -->
            </tbody>
        `;
        
        const tbody = table.querySelector('tbody');
        
        if (requests.length === 0) {
            // No requests found
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `<td colspan="9" style="text-align: center; padding: 2rem;">No requests found</td>`;
            tbody.appendChild(emptyRow);
        } else {
            // Add requests to table
            requests.forEach(request => {
                // Determine available actions based on status
                let actions = '';
                
                // Check if book is overdue
                const isOverdue = request.status === 'borrowed' && request.due_date && 
                                  new Date(request.due_date) < new Date();
                                  
                // Check if book was returned late
                const isReturnedLate = request.status === 'returned' && 
                                      wasReturnedLate(request.due_date, request.return_date);
                
                // Determine status class and text
                let statusClass = request.status;
                let statusText = formatStatus(
                    request.status, 
                    request.due_date, 
                    request.return_date
                );
                
                if (isOverdue) {
                    statusClass = 'overdue';
                } else if (isReturnedLate) {
                    statusClass = 'returned-late';
                }
                
                if (request.status === 'reserved') {
                    actions = `
                        <button class="action-btn approve-btn" data-id="${request.loan_id}">Approve</button>
                        <button class="action-btn reject-btn" data-id="${request.loan_id}">Reject</button>
                    `;
                } else if (request.status === 'ready_for_pickup') {
                    actions = `
                        <button class="action-btn complete-btn" data-id="${request.loan_id}">Mark Borrowed</button>
                        <button class="action-btn reject-btn" data-id="${request.loan_id}">Cancel</button>
                    `;
                } else if (request.status === 'borrowed') {
                    actions = `
                        <button class="action-btn return-btn" data-id="${request.loan_id}">Return</button>
                    `;
                }
                
                // Create row
                const row = document.createElement('tr');
                
                // Add class for highlighting
                if (isOverdue) {
                    row.classList.add('overdue-row');
                } else if (isReturnedLate) {
                    row.classList.add('returned-late-row');
                }
                
                row.innerHTML = `
                    <td data-label="ID">${request.loan_id}</td>
                    <td data-label="Type">
                        <span class="type-badge type-${request.status === 'borrowed' || request.status === 'returned' ? 'pickup' : 'reserve'}">
                            ${request.status === 'borrowed' || request.status === 'returned' ? 'Pickup' : 'Reservation'}
                        </span>
                    </td>
                    <td data-label="Book Title">${request.book_title}</td>
                    <td data-label="User">${request.username}</td>
                    <td data-label="Request Date">${formatDate(request.reserved_at)}</td>
                    <td data-label="Pickup Date">${request.picked_up_at ? formatDate(request.picked_up_at) : 
                        (request.status === 'ready_for_pickup' ? 'Ready for pickup' : '-')}</td>
                    <td data-label="Due Date">${request.due_date ? formatDate(request.due_date) : '-'}</td>
                    <td data-label="Status">
                        <span class="status-badge status-${statusClass}">
                            ${statusText}
                        </span>
                    </td>
                    <td data-label="Actions">
                        <div class="request-actions">
                            ${actions}
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        // Clear container and add table
        container.innerHTML = '';
        container.appendChild(table);
        
        // Re-attach event listeners
        setupActionButtons();
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }
    
    // Function to populate reserve and pickup tabs
    function populateTabContent(tabId) {
        const tabContent = document.getElementById(tabId);
        if (!tabContent) return;
        
        // Show loading indicator
        tabContent.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><p>Loading requests...</p></div>';
        
        // Determine request type based on tab
        const requestType = tabId === 'reserve-requests' ? 'reserve' : 'pickup';
        
        // Fetch filtered data from server
        fetch(`get_loan_requests.php?type=${requestType}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderRequestsTable(data.requests, tabContent);
                } else {
                    tabContent.innerHTML = `<div class="error-message">${data.message || 'Failed to load requests'}</div>`;
                }
            })
            .catch(error => {
                console.error('Error loading requests:', error);
                tabContent.innerHTML = '<div class="error-message">Failed to connect to server. Please try again.</div>';
            });
    }
    
    // Setup filter event listeners
    document.getElementById('status-filter').addEventListener('change', loadRequests);
    document.getElementById('type-filter').addEventListener('change', loadRequests);
    document.getElementById('date-filter').addEventListener('change', loadRequests);
    
    // Load all requests on page load
    loadRequests();
});
