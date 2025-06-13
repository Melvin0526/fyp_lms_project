document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            console.log(`Filter changed: ${this.value}`);
            // In a real application, you would filter the users based on the selected values
        });
    });
    
    // Button actions
    const createUserBtn = document.querySelector('.user-create-btn');
    if (createUserBtn) {
        createUserBtn.addEventListener('click', function() {
            alert('Add new user form will appear here');
        });
    }
    
    const viewButtons = document.querySelectorAll('.view-details-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const userId = row.cells[0].textContent;
            alert(`View details for user ID: ${userId}`);
        });
    });
    
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const userId = row.cells[0].textContent;
            alert(`Edit user with ID: ${userId}`);
        });
    });
    
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            const userId = row.cells[0].textContent;
            const username = row.cells[1].textContent;
            if (confirm(`Are you sure you want to delete user "${username}" (ID: ${userId})?`)) {
                alert(`User ${username} would be deleted in a real application`);
            }
        });
    });
    
    // Pagination
    const pageButtons = document.querySelectorAll('.page-btn');
    pageButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('active')) {
                document.querySelector('.page-btn.active').classList.remove('active');
                this.classList.add('active');
                // In a real application, you would fetch the users for the selected page
            }
        });
    });
});
