<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        $data = $_POST;
    }

    $fullName = trim($data['full_name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }

    if ($password !== $confirmPassword) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit;
    }

    if (strlen($password) < 8) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters.']);
        exit;
    }

    $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Email is already in use.']);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $sql = "INSERT INTO users (email, password_hash, full_name, role) VALUES (:email, :password_hash, :full_name, 'viewer')";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':full_name' => $fullName
        ]);
        
        $userId = $pdo->lastInsertId();

        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'full_name' => $fullName,
            'role' => 'viewer',
        ];

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'redirect' => '../map.php']);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unable to register: ' . $e->getMessage()]);
        exit;
    }
}

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    }

    header("Location: ../map.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="San Andreas - Live incident tracking and dispatch system">
    <title>Create account - San Andreas</title>

    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">

    <script>
        window.APP_BASE = '..';
        window.ASSET_BASE = '../assets';
    </script>
</head>
<body>
    <div class="auth-container" role="main">
        <div class="auth-card" aria-labelledby="register-heading">
            <div class="auth-header">
                <span class="eyebrow">Register</span>
                <h1 id="register-heading" class="auth-title">Create your account</h1>
                <p class="auth-subtitle">Fill in the form to create a new user account.</p>
            </div>

            <div id="authMessage" class="alert visually-hidden" role="status" aria-live="polite"></div>

            <form id="registerForm" class="auth-form" novalidate>
                <div class="form-group">
                    <label for="full_name" class="form-label">Full name</label>
                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        class="form-input"
                        placeholder="dalmat ademi"
                        autocomplete="name"
                    >
                </div>

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
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="create a password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle-btn" aria-label="Show password">Show</button>
                </div>

                <div class="form-group password-toggle">
                    <label for="confirm_password" class="form-label">Confirm password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="form-input"
                        placeholder="Repeat your password"
                        autocomplete="new-password"
                    >
                    <button type="button" class="password-toggle-btn" aria-label="Show password">Show</button>
                </div>

                <button type="submit" class="btn btn-primary auth-btn">Create account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
