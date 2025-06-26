// DOM elements
const hide_pass = document.getElementById('eyeicon');
const confirm_hide_pass = document.getElementById('confirm_eyeicon');
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirmPassword');
const registerForm = document.getElementById('registerForm');
const registerButton = document.getElementById('registerButton');
const errorContainer = document.getElementById('errorContainer');
const successContainer = document.getElementById('successContainer');
const usernameInput = document.querySelector('input[name="username"]');
const emailInput = document.querySelector('input[name="email"]');
const phoneInput = document.querySelector('input[name="phone"]');

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
      confirmPassword.setCustomValidity("Passwords don't match");
      confirmPassword.style.borderColor = '#dc3545';
    } else {
      confirmPassword.setCustomValidity('');
      confirmPassword.style.borderColor = '#28a745';
    }
  } else {
    // Reset styling if fields are empty
    confirmPassword.style.borderColor = '';
  }
}

// Add event listeners to both password fields
passwordFields.forEach(field => {
  field.addEventListener('input', checkPasswordMatch);
});

// Live validation for username (minimum length)
usernameInput.addEventListener('input', function() {
  if (this.value.length > 0) {
    if (this.value.length < 4) {
      this.style.borderColor = '#dc3545';
      this.setCustomValidity('Username must be at least 4 characters');
    } else {
      this.style.borderColor = '#28a745';
      this.setCustomValidity('');
    }
  } else {
    this.style.borderColor = '';
    this.setCustomValidity('');
  }
});

// Live validation for email format
emailInput.addEventListener('input', function() {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (this.value.length > 0) {
    if (!emailRegex.test(this.value)) {
      this.style.borderColor = '#dc3545';
      this.setCustomValidity('Please enter a valid email address');
    } else {
      this.style.borderColor = '#28a745';
      this.setCustomValidity('');
    }
  } else {
    this.style.borderColor = '';
    this.setCustomValidity('');
  }
});

// Live validation for phone number
phoneInput.addEventListener('input', function() {
  // Basic phone validation (can be adjusted for different formats)
  const phoneRegex = /^[0-9\-\+\(\)\s]{9,12}$/;
  if (this.value.length > 0) {
    if (!phoneRegex.test(this.value)) {
      this.style.borderColor = '#dc3545';
      this.setCustomValidity('Please enter a valid phone number');
    } else {
      this.style.borderColor = '#28a745';
      this.setCustomValidity('');
    }
  } else {
    this.style.borderColor = '';
    this.setCustomValidity('');
  }
});

// Live validation for password length
password.addEventListener('input', function() {
  if (this.value.length > 0) {
    if (this.value.length < 8) {
      this.style.borderColor = '#dc3545';
      this.setCustomValidity('Password must be at least 8 characters');
    } else {
      this.style.borderColor = '#28a745';
      this.setCustomValidity('');
    }
  } else {
    this.style.borderColor = '';
    this.setCustomValidity('');
  }
});

// Form submission handling
registerForm.addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Clear previous error/success messages
  errorContainer.style.display = 'none';
  errorContainer.textContent = '';
  successContainer.style.display = 'none';
  successContainer.textContent = '';
  
  // Disable button to prevent multiple submissions
  registerButton.disabled = true;
  registerButton.textContent = 'Processing...';
  
  // Get form data
  const formData = new FormData(registerForm);
  
  // Send AJAX request
  fetch('register_process.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Re-enable button
    registerButton.disabled = false;
    registerButton.textContent = 'Register';
    
    if (data.status === 'success') {
      // Show success message with fade-in effect
      successContainer.textContent = data.message;
      successContainer.style.display = 'block';
      successContainer.style.opacity = '0';
      
      // Fade in animation
      setTimeout(() => {
        successContainer.style.transition = 'opacity 0.5s';
        successContainer.style.opacity = '1';
      }, 10);
      
      // Redirect to login page after delay
      setTimeout(() => {
        window.location.href = 'login.php?success=registration_complete';
      }, 2000);
    } else {
      // Show error message with highlight effect
      errorContainer.textContent = data.message;
      errorContainer.style.display = 'block';
      errorContainer.style.animation = 'highlight 1.5s ease-in-out';
      
      // Highlight the field that caused the error
      if (data.message.toLowerCase().includes('username')) {
        usernameInput.style.borderColor = '#dc3545';
        usernameInput.focus();
      } else if (data.message.toLowerCase().includes('email')) {
        emailInput.style.borderColor = '#dc3545';
        emailInput.focus();
      } else if (data.message.toLowerCase().includes('password')) {
        password.style.borderColor = '#dc3545';
        confirmPassword.style.borderColor = '#dc3545';
        password.focus();
      }
    }
  })
  .catch(error => {
    console.error('Error:', error);
    errorContainer.textContent = 'An unexpected error occurred. Please try again.';
    errorContainer.style.display = 'block';
    registerButton.disabled = false;
    registerButton.textContent = 'Register';
  });
});