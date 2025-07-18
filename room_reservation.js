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
    
    // Elements
    const reservationDateInput = document.getElementById("reservation-date");
    const roomCards = document.querySelectorAll(".room-card");
    const reserveBtn = document.getElementById("reserve-btn");
    const summaryRoom = document.getElementById("summary-room");
    const summaryDate = document.getElementById("summary-date");
    const summaryTimeslot = document.getElementById("summary-timeslot");
    const timeslotSelection = document.getElementById("timeslot-selection");
    const timeslotsContainer = document.getElementById("timeslots-container");
    
    // Form input elements
    const roomIdInput = document.getElementById("room_id_input");
    const reservationDateInput2 = document.getElementById("reservation_date_input");
    const slotIdInput = document.getElementById("slot_id_input");
    
    // Current selections
    let selectedRoom = null;
    let selectedDate = null;
    let selectedTimeslot = null;
    
    // Set minimum date to today
    const today = new Date();
    const dd = String(today.getDate()).padStart(2, "0");
    const mm = String(today.getMonth() + 1).padStart(2, "0");
    const yyyy = today.getFullYear();
    const todayFormatted = yyyy + "-" + mm + "-" + dd;
    reservationDateInput.min = todayFormatted;
    
    // Format date for display
    function formatDateForDisplay(dateString) {
        const options = { weekday: "long", year: "numeric", month: "long", day: "numeric" };
        return new Date(dateString).toLocaleDateString("en-US", options);
    }
    
    // Check and update timeslots periodically if viewing today's date
    let timeslotRefreshInterval = null;

    // Function to fetch available timeslots
    function fetchTimeslots(roomId, date) {
        // Clear previous timeslots and show loading state
        timeslotsContainer.innerHTML = `
            <div class="timeslots-loading">
                <div class="loading-spinner"></div>
                <p>Loading available timeslots...</p>
            </div>
        `;
        
        // Show the timeslot selection section with animation
        timeslotSelection.style.opacity = '0';
        timeslotSelection.style.display = 'block';
        setTimeout(() => {
            timeslotSelection.style.opacity = '1';
            timeslotSelection.classList.add('slide-in');
        }, 50);
        
        // Reset selected timeslot
        selectedTimeslot = null;
        
        // Clear any existing refresh interval
        if (timeslotRefreshInterval) {
            clearInterval(timeslotRefreshInterval);
            timeslotRefreshInterval = null;
        }
        
        // Fetch available timeslots from server
        fetch(`get_timeslots.php?room_id=${roomId}&date=${date}`)
            .then(response => {
                // Check if response is OK before parsing JSON
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                
                // Try to parse the response as JSON
                return response.text().then(text => {
                    if (!text) {
                        throw new Error('Empty response from server');
                    }
                    
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Failed to parse JSON:', text.substring(0, 200) + '...');
                        throw new Error('Invalid JSON: ' + e.message);
                    }
                });
            })
            .then(data => {
                console.log("Timeslots response:", data); // Debug output
                
                if (data.error) {
                    timeslotsContainer.innerHTML = `<p class="error">Error: ${data.error}</p>`;
                    return;
                }
                
                // Render timeslots
                renderTimeslots(data);
                
                // If this is today's date, set up periodic refresh to keep timeslots current
                if (data.is_today) {
                    // Show a notice that timeslots are being updated in real-time
                    const timeNotice = document.createElement('div');
                    timeNotice.className = 'time-notice';
                    timeNotice.innerHTML = '<i class="fas fa-info-circle"></i> Viewing today\'s timeslots. You can book a room until one hour after its start time.';
                    timeslotsContainer.prepend(timeNotice);
                    
                    // Set up refresh interval (every minute)
                    timeslotRefreshInterval = setInterval(() => {
                        console.log("Auto-refreshing timeslots for today's date");
                        
                        // Fetch latest timeslots but don't show loading indicator
                        fetch(`get_timeslots.php?room_id=${roomId}&date=${date}`)
                            .then(response => response.json())
                            .then(newData => {
                                // Only update if there's been a change in available timeslots
                                const oldCount = data.timeslots.length;
                                const newCount = newData.timeslots.length;
                                
                                if (oldCount !== newCount || JSON.stringify(data.timeslots) !== JSON.stringify(newData.timeslots)) {
                                    console.log("Timeslots changed, updating display");
                                    data = newData;
                                    renderTimeslots(data, true);
                                }
                            })
                            .catch(error => {
                                console.error("Error refreshing timeslots:", error);
                            });
                    }, 60000); // Update every minute
                }
            })
            .catch(error => {
                console.error('Error fetching timeslots:', error);
                // Add more detailed error message with debugging help
                timeslotsContainer.innerHTML = `
                    <p class="error">Error fetching available timeslots. Please try again.</p>
                    <p>Details: Check that your database table 'timeslots' is correctly set up and contains active timeslots.</p>
                    <p>Technical info: ${error.message || 'Fetch operation failed'}</p>
                `;
            });
    }
    
    // Function to render timeslots
    function renderTimeslots(data, isRefresh = false) {
        if (data.timeslots && data.timeslots.length > 0) {
            // Remember the ID of the previously selected timeslot
            const previouslySelectedId = selectedTimeslot ? selectedTimeslot.dataset.slotId : null;
            
            // Add heading first
            let timeslotsHTML = '<div class="timeslot-alert"><i class="far fa-clock"></i> Please select a timeslot for your reservation. Available times are shown below.</div>';
            
            // If this is today, show current time with Malaysia timezone indication
            if (data.is_today) {
                timeslotsHTML += `<div class="current-time-indicator"><i class="fas fa-clock"></i> Current time: ${data.server_time}</div>`;
            }
            
            timeslotsHTML += '<div class="timeslot-grid">';
            
            data.timeslots.forEach(slot => {
                // Ensure we have all required fields
                const slotId = slot.slot_id || '';
                const displayText = slot.display_text || 'Timeslot';
                const startTime = slot.start_time || '';
                const endTime = slot.end_time || '';
                
                // Format the time for better display
                let formattedTime;
                if (startTime && endTime) {
                    const startObj = new Date(`2000-01-01T${startTime}`);
                    const endObj = new Date(`2000-01-01T${endTime}`);
                    
                    // Format times in 12-hour format
                    const startFormatted = startObj.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    const endFormatted = endObj.toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    formattedTime = `${startFormatted} - ${endFormatted}`;
                } else {
                    formattedTime = displayText;
                }
                
                // Create an icon based on the time of day
                let timeIcon = '<i class="far fa-sun"></i>'; // Default morning icon
                
                if (startTime) {
                    const hour = new Date(`2000-01-01T${startTime}`).getHours();
                    if (hour < 12) {
                        timeIcon = '<i class="far fa-sun"></i>'; // Morning
                    } else if (hour < 17) {
                        timeIcon = '<i class="fas fa-sun"></i>'; // Afternoon
                    } else {
                        timeIcon = '<i class="far fa-moon"></i>'; // Evening
                    }
                }
                
                // Check if this slot was previously selected
                const wasSelected = (slotId.toString() === previouslySelectedId);
                const selectedClass = wasSelected ? ' selected' : '';
                
                timeslotsHTML += `
                    <div class="timeslot-card${selectedClass}" data-slot-id="${slotId}">
                        <span class="timeslot-time">${timeIcon} ${displayText}</span>
                        <span class="timeslot-details">${formattedTime}</span>
                    </div>
                `;
            });
            
            timeslotsHTML += '</div>';
            timeslotsContainer.innerHTML = timeslotsHTML;
            
            // Add event listeners to timeslot cards
            document.querySelectorAll('.timeslot-card').forEach(card => {
                card.addEventListener('click', function() {
                    // Deselect all cards first
                    document.querySelectorAll('.timeslot-card').forEach(c => c.classList.remove('selected'));
                    
                    // Select this card with a small delay for visual effect
                    setTimeout(() => {
                        this.classList.add('selected');
                        
                        // Add check mark animation
                        const checkMark = document.createElement('span');
                        checkMark.className = 'check-mark';
                        checkMark.innerHTML = '<i class="fas fa-check"></i>';
                        this.appendChild(checkMark);
                        
                        // Remove the check mark after the animation completes
                        setTimeout(() => {
                            if (checkMark && checkMark.parentNode) {
                                checkMark.parentNode.removeChild(checkMark);
                            }
                        }, 600);
                    }, 50);
                    
                    selectedTimeslot = this;
                    
                    // Update summary
                    updateSummary();
                    
                    // Scroll to the summary section if not visible
                    const summaryElement = document.querySelector('.reservation-summary');
                    if (summaryElement) {
                        setTimeout(() => {
                            const rect = summaryElement.getBoundingClientRect();
                            if (rect.top < 0 || rect.bottom > window.innerHeight) {
                                summaryElement.scrollIntoView({behavior: 'smooth', block: 'center'});
                            }
                        }, 300);
                    }
                });
            });
            
            // If we're doing a refresh and had a selected timeslot that's no longer available
            if (isRefresh && previouslySelectedId && !document.querySelector(`.timeslot-card[data-slot-id="${previouslySelectedId}"]`)) {
                // The previously selected timeslot is no longer available
                selectedTimeslot = null;
                updateSummary();
                
                // Show a warning
                const warning = document.createElement('div');
                warning.className = 'warning-message';
                warning.innerHTML = '<i class="fas fa-exclamation-triangle"></i> The timeslot you previously selected is no longer available. Please select another timeslot.';
                timeslotsContainer.prepend(warning);
            } else if (isRefresh && previouslySelectedId) {
                // Re-select the previously selected timeslot
                selectedTimeslot = document.querySelector(`.timeslot-card[data-slot-id="${previouslySelectedId}"]`);
            }
        } else {
            timeslotsContainer.innerHTML = `
                <div class="no-timeslots">
                    <i class="far fa-calendar-times fa-3x"></i>
                    <h4>No Available Timeslots</h4>
                    <p>There are no available timeslots for the selected date and room.</p>
                    <p>Please try selecting a different date or room.</p>
                    <button class="change-date-btn" onclick="document.getElementById('reservation-date').focus()">
                        <i class="far fa-calendar-alt"></i> Change Date
                    </button>
                </div>
            `;
        }
    }
    
    // Handle date selection
    reservationDateInput.addEventListener("change", function(e) {
        selectedDate = e.target.value;
        console.log(`Date selected: ${selectedDate}`);
        
        // If both room and date are selected, fetch available timeslots
        if (selectedRoom && selectedDate) {
            fetchTimeslots(selectedRoom.dataset.roomId, selectedDate);
        } else {
            // Hide timeslot selection if either room or date is not selected
            timeslotSelection.style.display = 'none';
        }
        
        // Update summary
        updateSummary();
    });
    
    // Handle room selection
    roomCards.forEach(room => {
        room.addEventListener("click", function() {
            // Remove selected class from all rooms
            roomCards.forEach(r => r.classList.remove("selected"));
            
            // Select this room
            this.classList.add("selected");
            selectedRoom = this;
            console.log(`Room selected: ${this.querySelector("h4").textContent} (ID: ${this.dataset.roomId})`);
            
            // If both room and date are selected, fetch available timeslots
            if (selectedRoom && selectedDate) {
                fetchTimeslots(selectedRoom.dataset.roomId, selectedDate);
            } else {
                // Hide timeslot selection if either room or date is not selected
                timeslotSelection.style.display = 'none';
            }
            
            // Update summary
            updateSummary();
        });
    });
    
    // Update reservation summary
    function updateSummary() {
        // Update room info in the summary
        if (selectedRoom) {
            const roomName = selectedRoom.querySelector("h4").textContent;
            const roomCapacity = selectedRoom.querySelector("p:nth-of-type(1)").textContent;
            summaryRoom.innerHTML = `<span class="summary-highlight">${roomName}</span> <span class="summary-subinfo">${roomCapacity}</span>`;
        } else {
            summaryRoom.innerHTML = '<span class="summary-placeholder">Please select a room</span>';
        }
        
        // Update date info in the summary with formatted date
        if (selectedDate) {
            const formattedDate = formatDateForDisplay(selectedDate);
            // Get day of week
            const dayOfWeek = new Date(selectedDate).toLocaleString('en-US', {weekday: 'short'});
            summaryDate.innerHTML = `<span class="summary-highlight">${formattedDate}</span> <span class="summary-day">${dayOfWeek}</span>`;
        } else {
            summaryDate.innerHTML = '<span class="summary-placeholder">Please select a date</span>';
        }
        
        // Update timeslot info in the summary with more details
        if (selectedTimeslot) {
            // Get both the time label and the detailed time
            const timeLabel = selectedTimeslot.querySelector('.timeslot-time').textContent.trim();
            const timeDetails = selectedTimeslot.querySelector('.timeslot-details').textContent.trim();
            
            // Display both in the summary
            summaryTimeslot.innerHTML = `
                <span class="summary-highlight">${timeLabel}</span> 
                <span class="summary-subinfo">${timeDetails}</span>
            `;
        } else {
            summaryTimeslot.innerHTML = '<span class="summary-placeholder">Please select a timeslot</span>';
        }
        
        // Enable/disable reserve button and add animation effect
        if (selectedRoom && selectedDate && selectedTimeslot) {
            reserveBtn.disabled = false;
            reserveBtn.classList.add('ready');
        } else {
            reserveBtn.disabled = true;
            reserveBtn.classList.remove('ready');
        }
        
        // Update the hidden form values
        if (selectedRoom) roomIdInput.value = selectedRoom.dataset.roomId;
        if (selectedDate) reservationDateInput2.value = selectedDate;
        if (selectedTimeslot) slotIdInput.value = selectedTimeslot.dataset.slotId;
    }
    
    // Modal elements
    const modal = document.getElementById("confirmation-modal");
    const confirmRoom = document.getElementById("confirm-room");
    const confirmDate = document.getElementById("confirm-date");
    const confirmTimeslot = document.getElementById("confirm-timeslot");
    const confirmBtn = document.getElementById("confirm-btn");
    const cancelModalBtn = document.getElementById("cancel-modal-btn");
    const closeModal = document.querySelector(".close-modal");
    
    // Modal functions
    function openModal() {
        modal.style.display = "block";
        
        // Set room info with capacity
        const roomName = selectedRoom.querySelector("h4").textContent;
        const roomCapacity = selectedRoom.querySelector("p:nth-of-type(1)").textContent;
        confirmRoom.innerHTML = `${roomName} <small>(${roomCapacity})</small>`;
        
        // Set formatted date with day of week
        const formattedDate = formatDateForDisplay(selectedDate);
        const dayOfWeek = new Date(selectedDate).toLocaleString('en-US', {weekday: 'short'});
        confirmDate.innerHTML = `${formattedDate} <span class="day-badge">${dayOfWeek}</span>`;
        
        // Set detailed timeslot info
        const timeLabel = selectedTimeslot.querySelector('.timeslot-time').textContent.trim();
        const timeDetails = selectedTimeslot.querySelector('.timeslot-details').textContent.trim();
        confirmTimeslot.innerHTML = `${timeLabel} <span class="time-details">${timeDetails}</span>`;
        
        // Add animation
        document.querySelector('.modal-content').classList.add('animate-in');
    }
    
    function closeModalFunction() {
        // Add fade out animation
        const modalContent = document.querySelector('.modal-content');
        modalContent.classList.add('animate-out');
        
        // Wait for animation to complete before hiding
        setTimeout(() => {
            modal.style.display = "none";
            modalContent.classList.remove('animate-out');
            modalContent.classList.remove('animate-in');
        }, 300);
    }
    
    // Add event listeners for modal
    reserveBtn.addEventListener("click", function(e) {
        e.preventDefault();
        openModal();
    });
    
    closeModal.addEventListener("click", closeModalFunction);
    cancelModalBtn.addEventListener("click", closeModalFunction);
    
    window.addEventListener("click", function(e) {
        if (e.target === modal) {
            closeModalFunction();
        }
    });
    
    // Handle reservation confirmation
    confirmBtn.addEventListener("click", function(e) {
        e.preventDefault();
        
        // Display loading indicator
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        this.disabled = true;
        
        // Get form data
        const formData = new FormData();
        formData.append("room_id", selectedRoom.dataset.roomId);
        formData.append("reservation_date", selectedDate);
        formData.append("slot_id", selectedTimeslot.dataset.slotId);
        
        // Send AJAX request
        fetch("process_reservation.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                modal.style.display = "none";
                
                const successMsg = document.createElement("div");
                successMsg.className = "success-message";
                successMsg.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || "Reservation successful!");
                
                // Insert at beginning of form
                const form = document.getElementById("reservation-form");
                form.insertBefore(successMsg, form.firstChild);
                
                // Reset form
                roomCards.forEach(r => r.classList.remove("selected"));
                selectedRoom = null;
                reservationDateInput.value = "";
                
                updateSummary();
                
                // Reload page after delay to show updated reservations
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                // Show error message in modal
                const errorMsg = document.createElement("div");
                errorMsg.className = "error-message";
                errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || "An error occurred while creating your reservation.");
                
                // Insert before buttons
                document.querySelector(".modal-buttons").before(errorMsg);
                
                // Reset button state
                this.innerHTML = '<i class="fas fa-check"></i> Confirm Reservation';
                this.disabled = false;
            }
        })
        .catch(error => {
            console.error("Error:", error);
            
            // Reset button state
            this.innerHTML = '<i class="fas fa-check"></i> Confirm Reservation';
            this.disabled = false;
            
            // Show error message
            alert("An error occurred. Please try again.");
        });
    });
    
    // Modal-based form submission is now the only method, so remove duplicate code
    // ...existing code...
    
    // Event listeners for cancel buttons
    document.querySelectorAll(".cancel-btn").forEach(btn => {
        btn.addEventListener("click", function(e) {
            if (!confirm("Are you sure you want to cancel this reservation?")) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Event listeners for review buttons
    document.querySelectorAll(".review-btn").forEach(btn => {
        btn.addEventListener("click", function() {
            alert("Thank you for using our room reservation service!");
        });
    });
});

