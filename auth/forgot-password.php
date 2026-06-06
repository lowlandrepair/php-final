<?php
require_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? '../admin/dashboard.php' : '../map.php';
    header("Location: $redirect");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="San Andreas - Live incident tracking and dispatch system">
    <title>Forgot Password - San Andreas</title>

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

    <script>
        window.APP_BASE = '..';
        window.ASSET_BASE = '../assets';
    </script>
</head>
<body>
    <div class="auth-container" role="main">
        <div class="auth-card" aria-labelledby="forgot-heading">
            <div class="auth-header">
                <span class="eyebrow">Password</span>
                <h1 id="forgot-heading" class="auth-title">Forgot your password?</h1>
                <p class="auth-subtitle">Enter your email address.</p>
            </div>

            <div id="authMessage" class="alert visually-hidden" role="status" aria-live="polite"></div>

            <form id="forgotPasswordForm" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary auth-btn">Send reset link</button>
            </form>

            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
