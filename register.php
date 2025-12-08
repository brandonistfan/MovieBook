<?php
require_once 'config/database.php';

$pageTitle = "Register";
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $conn = getDBConnection();
        
        // Check if email already exists
        $checkQuery = "SELECT userId FROM users WHERE email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $result->close();
            $stmt->close();
            $error = 'Email already registered.';
        } else {
            $result->close();
            $stmt->close();
            // Hash password (use password_hash in production)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $insertQuery = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sss", $username, $email, $hashedPassword);
            
            if ($stmt->execute()) {
                // Get the newly created user's ID
                $newUserId = $conn->insert_id;
                $stmt->close();
                
                // Automatically log in the user
                $_SESSION['userId'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                $conn->close();
                // Redirect to home page
                header('Location: index.php');
                exit;
            } else {
                $stmt->close();
                $error = 'Registration failed. Please try again.';
            }
        }
        $conn->close();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Register</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="register.php" class="auth-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Register</button>
            </form>
            
            <p class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
