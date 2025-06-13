<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title> Create An Account </title>
  <link rel="stylesheet" href="register.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>

<body>
  <div class="auth-container">
    <form id="registerForm" class="auth-box">
      
      <h2>Create Account </h2>
      <p>Join our Library Management System !</p>
      
      <!-- Error message container -->
      <div id="errorContainer" class="error-message" style="display: none;"></div>
      <!-- Success message container -->
      <div id="successContainer" class="success-message" style="display: none;"></div>
      
      <label class="field-title">Username</label>
      <input type="text" name="username" placeholder="E.g: melvin3850" required />
      
      <label class="field-title">Email Address</label>
      <input type="email" name="email" placeholder="E.g: abc@gmail.com" required />
      
      <label class="field-title">Phone Number</label>
      <input type="text" name="phone" placeholder="E.g: 016-XXXXXXX" required />
      
      <label class="field-title">Password</label>
      <div class="password-container">
        <input type="password" name="password" id="password" placeholder="Create a password" required />
        <img src="img/hide_pass.png" id="eyeicon" class="password-toggle">
      </div>
      
      <label class="field-title">Confirm Password</label>
      <div class="password-container">
        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Retype your password" required />
        <img src="img/hide_pass.png" id="confirm_eyeicon" class="password-toggle">
      </div>
      <button type="submit" id="registerButton">Register</button>
      <p class="switch-link">Already have an account? <a href="login.php">Login</a></p>
    </form>
  </div>
  
  <script src="register.js"></script>
</body>
</html>
