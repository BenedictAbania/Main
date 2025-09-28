<?php
include 'database.php'; 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $safe_username = $conn->real_escape_string($username);
        $safe_email = $conn->real_escape_string($email);
        
        $check_query = "SELECT id FROM users WHERE username = '$safe_username' OR email = '$safe_email'";
        $check_result = $conn->query($check_query);

        if ($check_result === false) {
             $error = "Database query failed during check.";
        } elseif ($check_result->num_rows > 0) {
            $error = "Username or email is already taken. Please choose another.";
        } else {
            $safe_name = $conn->real_escape_string($name);
            $safe_password = $conn->real_escape_string($password); 

            $insert_query = "INSERT INTO users (name, email, username, password) 
                             VALUES ('$safe_name', '$safe_email', '$safe_username', '$safe_password')";
            
            if ($conn->query($insert_query) === TRUE) {
                $success = "Librarian account created successfully! You can now log in.";
            } else {
                $error = "Error during registration. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="assets/logStyles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
</head>
<body>
    <div class="signup-container">
        <h1>Create Account</h1>
        <p class="subtitle">For library staff registration only</p>
        
        <?php if ($error): ?>
            <div class="message-box error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message-box success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post" action="signup.php">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>

        <p class="login-link">
            Already have an account? <a href="login.php">Log in here</a>
        </p>
    </div>
</body>
</html>