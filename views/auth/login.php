<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Sign in - ' . APP_NAME;
require_once __DIR__ . '/../layouts/header.php';
?>

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
                    <a href="/php-final/public/index.php?route=forgot-password" class="form-link">Forgot password?</a>
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
            <p>New here? <a href="/php-final/public/index.php?route=register">Create an account</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


