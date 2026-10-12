<?php
// login.php - User authentication page
require_once __DIR__ . '/config.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['registered'])) {
    $success = "Account created successfully! You can now log in.";
}

if (isset($_GET['logout'])) {
    $success = "You have been logged out securely.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            sync_session_cookie();

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Task Manager Pro</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="auth-wrapper">
        <div class="auth-card ">
            <div class="auth-header">
                <div class="brand" style="justify-content: center; margin-bottom: 0.5rem;">
                    <div class="brand-icon">✓</div>
                    Task Manager
                </div>
                <p>Sign in to manage your tasks</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <span>✓</span> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="login_input">Username or Email</label>
                    <input type="text" id="login_input" name="login_input" class="form-control"
                        placeholder="Enter username or email" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control"
                        placeholder="Enter password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">Sign In</button>
            </form>

            <div style="text-align: center; margin-top: 1.25rem; font-size: 0.875rem;">
                <span style="color: var(--text-muted);">Don't have an account?</span>
                <a href="register.php" style="color: var(--accent-primary); text-decoration: none; font-weight: 600;">
                    Register here</a>
            </div>

            <div class="demo-credentials">
                <h4>🔑 Demo Accounts for Testing:</h4>
                <p style="margin-bottom: 0.25rem;"><strong>Admin (All Permissions):</strong><br>Username:
                    <code>admin</code> | Password: <code>admin123</code></p>
                <p><strong>Standard User (Own Tasks Only):</strong><br>Username: <code>john_doe</code> | Password:
                    <code>user123</code></p>
            </div>
        </div>
    </div>
</body>

</html>