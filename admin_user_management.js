document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const addUserModal = document.getElementById('add-user-modal');
    const editUserModal = document.getElementById('edit-user-modal');
    const deleteUserModal = document.getElementById('delete-user-modal');
    const userDetailsModal = document.getElementById('user-details-modal');
    
    // Modal buttons
    const addUserBtn = document.getElementById('add-user-btn');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    
    // User action buttons
    const viewDetailsBtns = document.querySelectorAll('.view-details-btn');
    const editBtns = document.querySelectorAll('.edit-btn');
    const deleteBtns = document.querySelectorAll('.delete-btn');
    
    // IMPORTANT: Remove the old event handlers to avoid conflicts
    // The event handlers from lines 3-52 are redundant with the ones below
    
    // Show Add User Modal
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            addUserModal.style.display = 'block';
        });
    }
    
    // Close modals
    closeModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            addUserModal.style.display = 'none';
            editUserModal.style.display = 'none';
            deleteUserModal.style.display = 'none';
            userDetailsModal.style.display = 'none';
        });
    });
    
    // Also close modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === addUserModal) {
            addUserModal.style.display = 'none';
        }
        if (event.target === editUserModal) {
            editUserModal.style.display = 'none';
        }
        if (event.target === deleteUserModal) {
            deleteUserModal.style.display = 'none';
        }
        if (event.target === userDetailsModal) {
            userDetailsModal.style.display = 'none';
        }
    });
    
    // View user details
    viewDetailsBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            
            console.log('View details for user ID:', userId);
            
            // Display the modal and loading message
            userDetailsModal.style.display = 'block';
            document.getElementById('user-details-content').innerHTML = '<div class="loading">Loading user details...</div>';
            
            // Fetch user details
            fetch(`user_process.php?action=get_user_details&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        // Create user details HTML
                        let detailsHTML = `
                            <div class="user-details">
                                <div class="user-detail-item">
                                    <span class="detail-label">Username:</span>
                                    <span class="detail-value">${user.username}</span>
                                </div>
                                <div class="user-detail-item">
                                    <span class="detail-label">Email:</span>
                                    <span class="detail-value">${user.email}</span>
                                </div>
                                <div class="user-detail-item">
                                    <span class="detail-label">Phone:</span>
                                    <span class="detail-value">${user.phone || 'Not provided'}</span>
                                </div>
                                <div class="user-detail-item">
                                    <span class="detail-label">Role:</span>
                                    <span class="detail-value role-badge">${user.usertype.charAt(0).toUpperCase() + user.usertype.slice(1)}</span>
                                </div>
                                <div class="user-detail-item">
                                    <span class="detail-label">Status:</span>
                                    <span class="detail-value status-badge status-${user.status.toLowerCase()}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span>
                                </div>
                            </div>
                        `;
                        
                        document.getElementById('user-details-content').innerHTML = detailsHTML;
                    } else {
                        document.getElementById('user-details-content').innerHTML = `
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> ${data.message || 'Failed to load user details.'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('user-details-content').innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> An error occurred while loading user details.
                        </div>
                    `;
                });
        });
    });
    
    // Edit user - Enhanced to ensure data is correctly loaded
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.userName;
            const email = this.dataset.userEmail;
            const phone = this.dataset.userPhone;
            const status = this.dataset.userStatus;
            const role = this.dataset.userRole;
            
            console.log('Editing user:', {
                userId, username, email, phone, status, role
            });
            
            // Fill in the edit form
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-username').value = username;
            document.getElementById('edit-email').value = email;
            document.getElementById('edit-phone').value = phone || '';
            document.getElementById('edit-status').value = status;
            document.getElementById('edit-usertype').value = role;
            document.getElementById('edit-password').value = '';
            
            // Show the modal
            editUserModal.style.display = 'block';
        });
    });
    
    // Alternative approach: Fetch user data from server for edit
    /*
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            
            // Show the modal with loading state
            editUserModal.style.display = 'block';
            document.getElementById('edit-user-form').innerHTML = '<div class="loading">Loading user data...</div>';
            
            // Fetch user details from server
            fetch(`user_process.php?action=get_user_details&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        
                        // Fill in the form with fetched data
                        document.getElementById('edit-user-id').value = user.id;
                        document.getElementById('edit-username').value = user.username;
                        document.getElementById('edit-email').value = user.email;
                        document.getElementById('edit-phone').value = user.phone || '';
                        document.getElementById('edit-status').value = user.status;
                        document.getElementById('edit-usertype').value = user.usertype;
                        document.getElementById('edit-password').value = '';
                        
                        // Show the form
                        document.getElementById('edit-user-form').style.display = 'block';
                    } else {
                        document.getElementById('edit-user-form').innerHTML = `
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i> Failed to load user data.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('edit-user-form').innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> An error occurred while loading user data.
                        </div>
                    `;
                });
        });
    });
    */
    
    // Delete user
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const username = this.dataset.userName;
            
            console.log('Delete user:', userId, username);
            
            // Set data in delete modal
            document.getElementById('delete-user-id').value = userId;
            document.getElementById('delete-user-name').textContent = username;
            
            // Show the modal
            deleteUserModal.style.display = 'block';
        });
    });
    
    // Form validations
    const addUserForm = document.getElementById('add-user-form');
    if (addUserForm) {
        addUserForm.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            if (password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long.');
            }
        });
    }
    
    const editUserForm = document.getElementById('edit-user-form');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(event) {
            const password = document.getElementById('edit-password').value;
            if (password && password.length < 8) {
                event.preventDefault();
                alert('Password must be at least 8 characters long.');
            }
        });
    }
});
