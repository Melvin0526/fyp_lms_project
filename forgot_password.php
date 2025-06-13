<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password</title>
  <link rel="stylesheet" href="forgot_password.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
</head>
<body>  
    <div class="auth-container">    
        <form action="reset_password_process.php" method="POST" class="auth-box">
            <a href="login.php" class="back-button" title="Back to Login"></a>      
            <h2>Forgot Password</h2>
            <p style="margin-bottom: 20px; color: #555;">Enter your email address below to reset your password. You'll be able to create a new password on the next screen.</p>            
            <?php if(isset($_GET['error'])): ?>
            <div class="error-message">
                <?php if($_GET['error'] == 'email_not_found') {
                        echo 'Email not found. Please check your email or register.';
                    } elseif($_GET['error'] == 'system_error') {
                        echo 'System error. Please try again later.';
                    } elseif($_GET['error'] == 'invalid_request') {
                        echo 'Invalid request. Please try again.';
                    }
                ?>
            </div>
            <?php endif; ?>
            
            <label class="field-title">Email Address</label>
            <input type="email" name="reset_email" placeholder="Enter your email" required />
            <button type="submit">Continue</button>
        </form>
    </div>
</body>
</html>
