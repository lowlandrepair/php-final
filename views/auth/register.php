<?php
require_once __DIR__ . '/../../config/config.php';
$pageTitle = 'Create account - ' . APP_NAME;
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="auth-container" role="main">
    <div class="auth-card" aria-labelledby="register-heading">
        <div class="auth-header">
            <div class="auth-logo"></div>
            <span class="eyebrow">Fast setup</span>
            <h1 id="register-heading" class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Register once and gain instant access to the Crime Map simulator and reporting tools.</p>
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
                <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">Show</button>
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
                <button type="button" class="password-toggle-btn" aria-label="Toggle password visibility">Show</button>
            </div>

            <button type="submit" class="btn btn-primary auth-btn">Create account</button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="/php-final/public/index.php?route=login">Sign in</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>


