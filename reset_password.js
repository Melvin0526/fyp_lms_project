// DOM elements
const hide_pass = document.getElementById('eyeicon');
const confirm_hide_pass = document.getElementById('confirm_eyeicon');
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const resetForm = document.getElementById('resetPasswordForm');
const resetButton = document.getElementById('resetButton');

// Toggle password visibility for main password field
hide_pass.onclick = function() {
  if (password.type === 'password') {
    password.type = 'text';
    hide_pass.src = 'img/show_pass.png';
  } else {
    password.type = 'password';
    hide_pass.src = 'img/hide_pass.png';
  }
}

// Toggle password visibility for confirm password field
confirm_hide_pass.onclick = function() {
  if (confirmPassword.type === 'password') {
    confirmPassword.type = 'text';
    confirm_hide_pass.src = 'img/show_pass.png';
  } else {
    confirmPassword.type = 'password';
    confirm_hide_pass.src = 'img/hide_pass.png';
  }
}

// Password match validation and real-time feedback
const passwordFields = [password, confirmPassword];

// Function to check password match and provide visual feedback
function checkPasswordMatch() {
  if (password.value && confirmPassword.value) {
    if (password.value !== confirmPassword.value) {
      confirmPassword.style.borderColor = '#dc3545';
      password.style.borderColor = '#dc3545';
    } else {
      confirmPassword.style.borderColor = '#28a745';
      password.style.borderColor = '#28a745';
    }
  } else {
    confirmPassword.style.borderColor = '';
    password.style.borderColor = '';
  }
}

// Add event listeners to both password fields
passwordFields.forEach(field => {
  field.addEventListener('input', checkPasswordMatch);
});

// Form submission validation
resetForm.addEventListener('submit', function(e) {
  if (password.value !== confirmPassword.value) {
    e.preventDefault();
    alert('Passwords do not match. Please try again.');
  }
  
  // Disable button to prevent multiple submissions
  resetButton.textContent = 'Processing...';
  resetButton.disabled = true;
});
