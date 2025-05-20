<?php
session_start();
require 'database.php';
$db = new Database();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $user = $db->login($email, $password);
    
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['2fa_required'] = $user['two_factor_enabled'];
        header("Location: verify_2fa.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Invalid credentials";
        header("Location: login.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <!-- Existing head content -->
</head>
<body class="bg-primary bg-gradient">
<div class="container">
    <div class="row min-vh-100 justify-content-center align-items-center">
        <div class="col-lg-5">
            <div class="card shadow">
                <div class="card-header">
                    <h1 class="fw-bold text-secondary">Login</h1>
                </div>
                <div class="card-body p-5">
                    <?php if (isset($_SESSION['login_error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['login_error'] ?></div>
                        <?php unset($_SESSION['login_error']) ?>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button class="btn btn-glass">Login</button>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="forgot.php" class="text-muted">Forgot Password?</a> | 
                            <a href="register.php" class="text-muted">Register</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
