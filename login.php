<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Library Login</title>
  <link rel="stylesheet" href="login.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>
  <div class="auth-container">
    <form action="login_process.php" method="POST" class="auth-box">
      <h2>Welcome Back ! </h2>
      
      <?php
      // Display success messages
      if(isset($_GET['success'])) {
        echo '<div class="success-message">';
        switch($_GET['success']) {
          case 'registration_complete':
            echo 'Registration successful! You can now login with your credentials.';
            break;
          case 'password_reset':
            echo 'Your password has been reset successfully! You can now login with your new password.';
            break;
          default:
            echo 'Operation completed successfully.';
        }
        echo '</div>';
      }
      
      // Display error messages
      if(isset($_GET['error'])) {
        echo '<div class="error-message">';
        switch($_GET['error']) {
          case 'invalid_credentials':
            echo 'Invalid password. Please try again.';
            break;
          case 'user_not_found':
            echo 'Username or email not found. Please check your credentials or register.';
            break;
          default:
            echo 'An error occurred during login. Please try again.';
        }
        echo '</div>';
      }
      ?>
      
      <label class="field-title">Username or Email</label>
      <input type="text" name="username" placeholder="Enter your username or email" required />
      
      <label class="field-title">Password</label>
      <div class="password-container">
        <input type="password" name="password" id="password" placeholder="Enter your password" required />
        <img src="img/hide_pass.png" id="eyeicon" class="password-toggle">
      </div>
      <div class="forgot-password">
        <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
      </div>
      <button type="submit">Login</button>
      <p class="switch-link">Don't have an account? <a href="register.php">Register</a></p>
    </form>
  </div>
  
  <script src="login.js"></script>
</body>
</html>
