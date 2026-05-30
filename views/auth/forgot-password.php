<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Forgot Password - ' . APP_NAME;
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-container" role="main">
    <div class="auth-card" aria-labelledby="forgot-heading">
        <div class="auth-header">
            <div class="auth-logo"></div>
            <span class="eyebrow">Password recovery</span>
            <h1 id="forgot-heading" class="auth-title">Forgot your password?</h1>
            <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
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
            <p>Remember your password? <a href="/php-final/public/index.php?route=login">Sign in</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
