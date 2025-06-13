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
    
    // Filter functionality
    const statusFilter = document.getElementById('status-filter');
    const typeFilter = document.getElementById('type-filter');
    const datePicker = document.querySelector('.date-picker');
    
    if (statusFilter && typeFilter && datePicker) {
        statusFilter.addEventListener('change', filterRequests);
        typeFilter.addEventListener('change', filterRequests);
        datePicker.addEventListener('change', filterRequests);
    }
    
    function filterRequests() {
        if (!statusFilter || !typeFilter || !datePicker) return;
        
        const status = statusFilter.value;
        const type = typeFilter.value;
        const date = datePicker.value;
        
        console.log(`Filtering requests - Status: ${status}, Type: ${type}, Date: ${date}`);
        // In a real application, you would filter the requests based on the selected values
        
        // Example filter logic for demo (just an alert)
        alert(`Filter applied - Status: ${status || 'All'}, Type: ${type || 'All'}, Date: ${date || 'All'}`);
    }
    
    // Action buttons functionality
    const approveButtons = document.querySelectorAll('.approve-btn');
    approveButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            alert(`Request ID ${requestId} has been approved`);
            // In a real app, you would send an AJAX request and update the UI
        });
    });
    
    const rejectButtons = document.querySelectorAll('.reject-btn');
    rejectButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            if (confirm(`Are you sure you want to reject request ID ${requestId}?`)) {
                alert(`Request ID ${requestId} has been rejected`);
                // In a real app, you would send an AJAX request and update the UI
            }
        });
    });
    
    const markReadyButtons = document.querySelectorAll('.mark-ready-btn');
    markReadyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            alert(`Request ID ${requestId} has been marked as ready for pickup`);
            // In a real app, you would send an AJAX request and update the UI
        });
    });
    
    const completeButtons = document.querySelectorAll('.complete-btn');
    completeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            alert(`Request ID ${requestId} has been marked as completed`);
            // In a real app, you would send an AJAX request and update the UI
        });
    });
    
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-id');
            alert(`Viewing details for request ID ${requestId}`);
            // In a real app, you would show a modal with request details
        });
    });
    
    // Pagination
    const pageButtons = document.querySelectorAll('.page-btn');
    pageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                document.querySelector('.page-btn.active').classList.remove('active');
                this.classList.add('active');
                // In a real application, you would fetch the requests for the selected page
            }
        });
    });
    
    // Function to populate reserve and pickup tabs
    function populateTabContent(tabId) {
        const tabContent = document.getElementById(tabId);
        if (!tabContent) return;
        
        // Clear existing content
        tabContent.innerHTML = '';
        
        // Create filters
        const filtersDiv = document.createElement('div');
        filtersDiv.className = 'request-filters';
        filtersDiv.innerHTML = `
            <div class="filter-group">
                <select class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="ready">Ready</option>
                    <option value="completed">Completed</option>
                    <option value="canceled">Canceled</option>
                </select>
                
                <input type="date" class="date-picker" placeholder="Filter by date">
            </div>
        `;
        
        // Create table
        const table = document.createElement('table');
        table.className = 'request-table';
        table.innerHTML = `
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Book Title</th>
                    <th>User</th>
                    <th>Request Date</th>
                    <th>Pickup Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Will be populated dynamically -->
            </tbody>
        `;
        
        // Create pagination
        const paginationDiv = document.createElement('div');
        paginationDiv.className = 'pagination';
        paginationDiv.innerHTML = `
            <button class="page-btn">&laquo;</button>
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">&raquo;</button>
        `;
        
        // Add everything to the tab content
        tabContent.appendChild(filtersDiv);
        tabContent.appendChild(table);
        tabContent.appendChild(paginationDiv);
        
        // Filter the requests based on tab type
        const type = tabId === 'reserve-requests' ? 'reserve' : 'pickup';
        
        // In a real app, you would send an AJAX request to get the filtered data
        // For this demo, we'll just show an alert
        alert(`Tab "${type}" content would be loaded from the server`);
    }
});
