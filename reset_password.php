<?php
// Start session
session_start();

// Include database connection file
include 'config.php';

// Check if email is stored in session
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php?error=invalid_request");
    exit();
}

$email = $_SESSION['reset_email'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
  <link rel="stylesheet" href="reset_password.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>  
    <div class="auth-container">    
        <form id="resetPasswordForm" action="reset_password_update.php" method="POST" class="auth-box">
            <a href="forgot_password.php" class="back-button" title="Back to Forgot Password"></a>            
            <h2>Reset Password</h2>
            <p style="margin-bottom: 20px; color: #555;">Please enter your new password below.</p>
            <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <?php 
                    if($_GET['error'] == 'password_mismatch') {
                        echo 'Passwords do not match. Please try again.';
                    } elseif($_GET['error'] == 'password_too_short') {
                        echo 'Password must be at least 8 characters long.';
                    } elseif($_GET['error'] == 'system_error') {
                        echo 'System error. Please try again later.';
                    }
                ?>
            </div>
            <?php endif; ?>
            
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <label class="field-title">New Password</label>
            <div class="password-container">
                <input type="password" name="password" id="password" placeholder="Enter new password" required />
                <img src="img/hide_pass.png" id="eyeicon" class="password-toggle">
            </div>
            
            <label class="field-title">Confirm Password</label>
            <div class="password-container">
                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" required />
                <img src="img/hide_pass.png" id="confirm_eyeicon" class="password-toggle">
            </div>            <button type="submit" id="resetButton">Reset Password</button>
        </form>
    </div>
    
    <script src="reset_password.js"></script>
</body>
</html>
