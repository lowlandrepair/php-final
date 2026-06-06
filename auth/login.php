<?php
require_once '../config.php';

// Handle AJAX POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (!$data) {
        $data = $_POST;
    }

    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if ($email === '' || $password === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
        exit;
    }

    $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user === false || !password_verify($password, $user['password_hash'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    // Set session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
    ];

    $redirect = $user['role'] === 'admin' ? '../admin/dashboard.php' : '../map.php';

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => $redirect]);
    exit;
}

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
    <title>Sign in - San Andreas</title>

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

    <script>
        window.APP_BASE = '..';
        window.ASSET_BASE = '../assets';
    </script>
</head>
<body>
    <div class="auth-container" role="main">
        <div class="auth-card" aria-labelledby="login-heading">
            <div class="auth-header">
                <span class="eyebrow">Login</span>
                <h1 id="login-heading" class="auth-title">Sign in</h1>
                <p class="auth-subtitle">Enter your email and password to continue.</p>
            </div>

            <div id="authMessage" class="alert visually-hidden" role="status" aria-live="polite"></div>

            <form id="loginForm" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-input"
                        placeholder="you@example.com"
                        autocomplete="email"
                    >
                </div>

                <div class="form-group password-toggle">
                    <div class="form-row">
                        <label for="password" class="form-label">Password</label>
                        <a href="forgot-password.php" class="form-link">Forgot password?</a>
                    </div>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle-btn" aria-label="Show password">Show</button>
                </div>

                <button type="submit" class="btn btn-primary auth-btn">Sign in</button>
            </form>

            <div class="auth-footer">
                <p>New here? <a href="register.php">Create an account</a></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
