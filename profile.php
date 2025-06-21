<?php
session_start();

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$success_message = "";
$error_message = "";

// Fetch user data including phone
$sql = "SELECT username, email, phone FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $email = $user['email'];
    $phone = isset($user['phone']) ? $user['phone'] : '';
} else {
    $error_message = "User information not found.";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $newUsername = trim($_POST['username']);
        $newEmail = trim($_POST['email']);
        $newPhone = trim($_POST['phone']);

        // Validate input
        if (empty($newUsername) || empty($newEmail)) {
            $error_message = "Username and email are required fields.";
        } else {
            // Check if new username already exists for another user
            $checkUsername = "SELECT id FROM users WHERE username = ? AND id != ?";
            $stmtCheck = $conn->prepare($checkUsername);
            $stmtCheck->bind_param("si", $newUsername, $userId);
            $stmtCheck->execute();
            $usernameExists = $stmtCheck->get_result()->num_rows > 0;
            $stmtCheck->close();

            // Check if new email already exists for another user
            $checkEmail = "SELECT id FROM users WHERE email = ? AND id != ?";
            $stmtCheck = $conn->prepare($checkEmail);
            $stmtCheck->bind_param("si", $newEmail, $userId);
            $stmtCheck->execute();
            $emailExists = $stmtCheck->get_result()->num_rows > 0;
            $stmtCheck->close();

            if ($usernameExists) {
                $error_message = "Username already exists. Please choose a different username.";
            } elseif ($emailExists) {
                $error_message = "Email already exists. Please use a different email.";
            } else {
                // Update user information
                $updateSql = "UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?";
                $stmtUpdate = $conn->prepare($updateSql);
                $stmtUpdate->bind_param("sssi", $newUsername, $newEmail, $newPhone, $userId);
                
                if ($stmtUpdate->execute()) {
                    // Update session variables
                    $_SESSION['username'] = $newUsername;
                    $_SESSION['email'] = $newEmail;
                    $username = $newUsername;
                    $email = $newEmail;
                    $phone = $newPhone;
                    $success_message = "Profile updated successfully.";
                } else {
                    $error_message = "Error updating profile: " . $conn->error;
                }
                $stmtUpdate->close();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        $passwordSql = "SELECT password FROM users WHERE id = ?";
        $stmtPass = $conn->prepare($passwordSql);
        $stmtPass->bind_param("i", $userId);
        $stmtPass->execute();
        $passwordResult = $stmtPass->get_result();
        
        if ($passwordResult->num_rows > 0) {
            $user = $passwordResult->fetch_assoc();
            $hashedPassword = $user['password'];
            
            if (password_verify($currentPassword, $hashedPassword)) {
                // Check if new passwords match
                if ($newPassword === $confirmPassword) {
                    if (strlen($newPassword) < 6) {
                        $error_message = "Password must be at least 6 characters long.";
                    } else {
                        // Hash new password and update
                        $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                        $updatePassSql = "UPDATE users SET password = ? WHERE id = ?";
                        $stmtUpdatePass = $conn->prepare($updatePassSql);
                        $stmtUpdatePass->bind_param("si", $hashedNewPassword, $userId);
                        
                        if ($stmtUpdatePass->execute()) {
                            $success_message = "Password updated successfully.";
                        } else {
                            $error_message = "Error updating password: " . $conn->error;
                        }
                        $stmtUpdatePass->close();
                    }
                } else {
                    $error_message = "New passwords do not match.";
                }
            } else {
                $error_message = "Current password is incorrect.";
            }
        }
        $stmtPass->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Library System</title>
    <link rel="stylesheet" href="profile.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <h1>Library System (LMS)</h1>
            </div>            
            <nav>
                <ul>
                    <li><a href="homepage.php">Home</a></li>
                    <li><a href="book_reservation.php">Browse Book</a></li>
                    <li><a href="borrow_history.php">Borrow History</a></li>
                    <li><a href="room_reservation.php">Room Reservation</a></li>
                    <li><a href="reservation_history.php">Reservation History</a></li>
                </ul>
            </nav>
            <div class="user-menu">
                <div class="user-info">
                    <span><?php echo htmlspecialchars($username); ?></span>                    
                    <div class="dropdown-content">
                        <a href="profile.php" class="active">My Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main>
            <section class="profile-section">
                <h2>My Profile</h2>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <div class="profile-container">
                    <div class="profile-info">
                        <h3>Personal Information</h3>
                        <form action="profile.php" method="post">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="profile-security">
                        <h3>Change Password</h3>
                        <form action="profile.php" method="post">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small>Password must be at least 6 characters</small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="profile.js"></script>
</body>
</html>
