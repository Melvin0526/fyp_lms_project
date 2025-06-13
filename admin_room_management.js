// Updated: June 13, 2025
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
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
        });
    });
    
    // Date filter and username search functionality
    const dateFilter = document.getElementById('booking-date-filter');
    const usernameSearch = document.getElementById('username-search');
    const roomSearch = document.getElementById('room-search');
    
    // Don't auto-submit when fields change to allow combined filtering
    if (dateFilter && usernameSearch && roomSearch) {
        // Handle Enter key press in username search field
        usernameSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('date-filter-form').submit();
            }
        });
        
        // Handle Enter key press in room search field
        roomSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('date-filter-form').submit();
            }
        });
    }
    
    // View reservation modal functionality
    const viewButtons = document.querySelectorAll('.view-btn');
    const modal = document.getElementById('view-reservation-modal');
    const closeModal = document.querySelector('.close-modal');
    const reservationDetails = document.getElementById('reservation-details');
    
    if (viewButtons.length > 0 && modal) {
        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                
                // Show loading state
                reservationDetails.innerHTML = '<div class="loading">Loading reservation details...</div>';
                modal.style.display = 'block';
                
                // Fetch reservation details via AJAX
                fetch(`get_reservation_details.php?id=${reservationId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        // Format and display the reservation details
                        const statusClass = `status-${data.status.toLowerCase()}`;
                        
                        reservationDetails.innerHTML = `
                            <div class="reservation-detail-item">
                                <span class="detail-label">Reservation ID:</span>
                                <span class="detail-value">#${data.reservation_id}</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">User:</span>
                                <span class="detail-value">${data.username}</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Contact:</span>
                                <span class="detail-value">
                                    ${data.user_email ? '<div>' + data.user_email + '</div>' : ''}
                                    ${data.user_phone ? '<div>Phone: ' + data.user_phone + '</div>' : ''}
                                </span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Room:</span>
                                <span class="detail-value">${data.room_name} (Capacity: ${data.room_capacity})</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Features:</span>
                                <span class="detail-value">${data.room_features || 'None specified'}</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Date:</span>
                                <span class="detail-value">${data.date} (${data.day_of_week})</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Time:</span>
                                <span class="detail-value">${data.time}</span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Status:</span>
                                <span class="detail-value booking-status ${statusClass}">
                                    ${data.status.charAt(0).toUpperCase() + data.status.slice(1)}
                                </span>
                            </div>
                            <div class="reservation-detail-item">
                                <span class="detail-label">Created:</span>
                                <span class="detail-value">${data.created_at}</span>
                            </div>
                        `;
                        
                        // If the reservation can be cancelled, add a cancel button
                        if (data.status === 'confirmed') {
                            reservationDetails.innerHTML += `
                                <div class="reservation-actions">
                                    <a href="?cancel_reservation=${data.reservation_id}" 
                                       class="booking-action-btn cancel-btn"
                                       onclick="return confirm('Are you sure you want to cancel this reservation?')">
                                        Cancel Reservation
                                    </a>
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        reservationDetails.innerHTML = `
                            <div class="error-message">
                                <i class="fas fa-exclamation-circle"></i>
                                Error loading reservation details: ${error.message}
                            </div>
                        `;
                    });
            });
        });
        
        // Close modal when clicking the X
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside of it
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Add confirmation for cancel buttons
    const cancelButtons = document.querySelectorAll('.cancel-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to cancel this reservation?')) {
                e.preventDefault();
            }
        });
    });
    
    // Room edit functionality
    const editRoomModal = document.getElementById('edit-room-modal');
    const editButtons = document.querySelectorAll('.edit-btn');
    const cancelEditButton = document.getElementById('cancel-edit');
    
    if (editButtons.length > 0 && editRoomModal) {
        // Handle edit button clicks
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const roomId = this.getAttribute('data-id');
                
                // Get room data from the card
                const roomCard = this.closest('.room-card');
                const roomName = roomCard.querySelector('.room-name').textContent;
                const capacityText = roomCard.querySelector('.info-item:first-child .info-value').textContent;
                const capacity = parseInt(capacityText);
                const features = roomCard.querySelector('.info-item:last-child .info-value').textContent;
                const isActive = roomCard.querySelector('.room-status').classList.contains('status-available') ? '1' : '0';
                
                // Populate the form
                document.getElementById('edit-room-id').value = roomId;
                document.getElementById('edit-room-name').value = roomName;
                document.getElementById('edit-capacity').value = capacity;
                document.getElementById('edit-features').value = features;
                
                // Show the modal
                editRoomModal.style.display = 'block';
            });
        });
        
        // Close modal when clicking cancel
        if (cancelEditButton) {
            cancelEditButton.addEventListener('click', function() {
                editRoomModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeEditModal = editRoomModal.querySelector('.close-modal');
        if (closeEditModal) {
            closeEditModal.addEventListener('click', function() {
                editRoomModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editRoomModal) {
                editRoomModal.style.display = 'none';
            }
        });
    }
    
    // Add New Room Modal Functionality
    const createRoomBtn = document.querySelector('.create-room-btn');
    const addRoomModal = document.getElementById('add-room-modal');
    const cancelAddButton = document.getElementById('cancel-add');
    
    if (createRoomBtn && addRoomModal) {
        // Open the add room modal when clicking "Add New Room" button
        createRoomBtn.addEventListener('click', function() {
            // Clear the form
            document.getElementById('add-room-form').reset();
            
            // Show the modal
            addRoomModal.style.display = 'block';
        });
        
        // Close modal when clicking cancel
        if (cancelAddButton) {
            cancelAddButton.addEventListener('click', function() {
                addRoomModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeAddModal = addRoomModal.querySelector('.close-modal');
        if (closeAddModal) {
            closeAddModal.addEventListener('click', function() {
                addRoomModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addRoomModal) {
                addRoomModal.style.display = 'none';
            }
        });
    }
    
    // Update the edit form to include the action field
    const editRoomForm = document.getElementById('edit-room-form');
    if (editRoomForm) {
        // Add the action field to distinguish between edit and create actions
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_room';
        editRoomForm.prepend(actionInput);
    }
    
    // Update the form submission to add validation
    const allRoomForms = document.querySelectorAll('#edit-room-form, #add-room-form');
    allRoomForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const roomNameInput = this.querySelector('[name="room_name"]');
            const capacityInput = this.querySelector('[name="capacity"]');
            
            let isValid = true;
            let errorMessage = '';
            
            // Simple validation
            if (!roomNameInput.value.trim()) {
                errorMessage += "Room name is required.\n";
                isValid = false;
            }
            
            if (!capacityInput.value || isNaN(capacityInput.value) || capacityInput.value < 1) {
                errorMessage += "Capacity must be a valid number greater than 0.\n";
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert("Please correct the following errors:\n" + errorMessage);
            }
        });
    });
    
    // Timeslot Management
    const addTimeslotBtn = document.getElementById('add-timeslot-btn');
    const addTimeslotModal = document.getElementById('add-timeslot-modal');
    const editTimeslotModal = document.getElementById('edit-timeslot-modal');
    const editTimeslotBtns = document.querySelectorAll('.edit-timeslot-btn');
    const cancelAddTimeslotBtn = document.getElementById('cancel-add-timeslot');
    const cancelEditTimeslotBtn = document.getElementById('cancel-edit-timeslot');
    
    // Add Timeslot Modal
    if (addTimeslotBtn && addTimeslotModal) {
        addTimeslotBtn.addEventListener('click', function() {
            addTimeslotModal.style.display = 'block';
        });
        
        // Close modal when clicking cancel
        if (cancelAddTimeslotBtn) {
            cancelAddTimeslotBtn.addEventListener('click', function() {
                addTimeslotModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeAddTimeslotModal = addTimeslotModal.querySelector('.close-modal');
        if (closeAddTimeslotModal) {
            closeAddTimeslotModal.addEventListener('click', function() {
                addTimeslotModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addTimeslotModal) {
                addTimeslotModal.style.display = 'none';
            }
        });
    }
    
    // Edit Timeslot Modal
    if (editTimeslotBtns.length > 0 && editTimeslotModal) {
        editTimeslotBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const slotId = this.getAttribute('data-id');
                const startTime = this.getAttribute('data-start');
                const endTime = this.getAttribute('data-end');
                const displayText = this.getAttribute('data-display');
                const isActive = this.getAttribute('data-active');
                
                // Populate form
                document.getElementById('edit-timeslot-id').value = slotId;
                document.getElementById('edit-display-text').value = displayText;
                document.getElementById('edit-start-time').value = startTime;
                document.getElementById('edit-end-time').value = endTime;
                document.getElementById('edit-timeslot-status').value = isActive;
                
                // Show modal
                editTimeslotModal.style.display = 'block';
            });
        });
        
        // Close modal when clicking cancel
        if (cancelEditTimeslotBtn) {
            cancelEditTimeslotBtn.addEventListener('click', function() {
                editTimeslotModal.style.display = 'none';
            });
        }
        
        // Close modal with X button
        const closeEditTimeslotModal = editTimeslotModal.querySelector('.close-modal');
        if (closeEditTimeslotModal) {
            closeEditTimeslotModal.addEventListener('click', function() {
                editTimeslotModal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === editTimeslotModal) {
                editTimeslotModal.style.display = 'none';
            }
        });
    }
    
    // Form validation for timeslot forms
    const timeslotForms = document.querySelectorAll('#edit-timeslot-form, #add-timeslot-form');
    timeslotForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const displayText = this.querySelector('[name="display_text"]').value;
            const startTime = this.querySelector('[name="start_time"]').value;
            const endTime = this.querySelector('[name="end_time"]').value;
            
            let isValid = true;
            let errorMessage = '';
            
            // Simple validation
            if (!displayText.trim()) {
                errorMessage += "Display name is required.\n";
                isValid = false;
            }
            
            if (!startTime.trim()) {
                errorMessage += "Start time is required.\n";
                isValid = false;
            }
            
            if (!endTime.trim()) {
                errorMessage += "End time is required.\n";
                isValid = false;
            }
            
            // Check that end time is after start time
            if (startTime && endTime && startTime >= endTime) {
                errorMessage += "End time must be after start time.\n";
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                alert("Please correct the following errors:\n" + errorMessage);
            }
        });
    });
});
