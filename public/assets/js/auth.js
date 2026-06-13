const loginForm = document.getElementById('loginForm');
const registerForm = document.getElementById('registerForm');
const forgotPasswordForm = document.getElementById('forgotPasswordForm');
const authMessage = document.getElementById('authMessage');
const passwordToggles = document.querySelectorAll('.password-toggle-btn');
let isSubmitting = false;

document.addEventListener('DOMContentLoaded', function() {
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    if (registerForm) {
        registerForm.addEventListener('submit', handleRegisterSubmit);
    }

    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', handleForgotPasswordSubmit);
    }

    passwordToggles.forEach(function(toggle) {
        toggle.addEventListener('click', togglePasswordVisibility);
    });

    displayFlashMessages();
});

async function handleLoginSubmit(event) {
    event.preventDefault();
    if (isSubmitting) {
        return;
    }

    clearFormErrors(loginForm);
    hideAuthMessage();

    const formData = new FormData(loginForm);
    const data = Object.fromEntries(formData.entries());
    const errors = validateLoginForm(data);

    if (Object.keys(errors).length > 0) {
        displayErrors(errors, loginForm);
        showAuthMessage('Please fix the errors above to continue.', 'error');
        return;
    }

    isSubmitting = true;
    const submitBtn = loginForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Signing in...';

    try {
        const response = await fetch('login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showAuthMessage(result.message, 'error');
        }
    } catch (error) {
        showAuthMessage('Unable to sign in right now. Please try again later.', 'error');
        console.error(error);
    } finally {
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

async function handleRegisterSubmit(event) {
    event.preventDefault();
    if (isSubmitting) {
        return;
    }

    clearFormErrors(registerForm);
    hideAuthMessage();

    const formData = new FormData(registerForm);
    const data = Object.fromEntries(formData.entries());
    const errors = validateRegisterForm(data);

    if (Object.keys(errors).length > 0) {
        displayErrors(errors, registerForm);
        showAuthMessage('Please correct the highlighted fields.', 'error');
        return;
    }

    isSubmitting = true;
    const submitBtn = registerForm.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner"></span> Creating account...';

    try {
        const response = await fetch('register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            window.location.href = result.redirect;
        } else {
            showAuthMessage(result.message, 'error');
        }
    } catch (error) {
        showAuthMessage('Unable to create your account right now. Please try again later.', 'error');
        console.error(error);
    } finally {
        isSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    }
}

function togglePasswordVisibility(event) {
    const button = event.currentTarget;
    const group = button.closest('.password-toggle');
    const input = group ? group.querySelector('input') : null;

    if (!input) {
        return;
    }

    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = 'Hide';
        button.setAttribute('aria-label', 'Hide password');
    } else {
        input.type = 'password';
        button.textContent = 'Show';
        button.setAttribute('aria-label', 'Show password');
    }
}

function handleForgotPasswordSubmit(event) {
    event.preventDefault();
    clearFormErrors(forgotPasswordForm);
    hideAuthMessage();

    const formData = new FormData(forgotPasswordForm);
    const data = Object.fromEntries(formData.entries());

    if (!data.email) {
        displayErrors({ email: 'Email is required.' }, forgotPasswordForm);
        showAuthMessage('Please enter your email address.', 'error');
        return;
    }

    if (!isValidEmail(data.email)) {
        displayErrors({ email: 'Enter a valid email address.' }, forgotPasswordForm);
        showAuthMessage('Please enter a valid email address.', 'error');
        return;
    }

    showAuthMessage('Password reset is not available yet.', 'info');
}

function validateLoginForm(data) {
    const errors = {};
    if (!data.email) {
        errors.email = 'Email is required.';
    } else if (!isValidEmail(data.email)) {
        errors.email = 'Enter a valid email address.';
    }
    if (!data.password) {
        errors.password = 'Password is required.';
    }
    return errors;
}

function validateRegisterForm(data) {
    const errors = {};
    if (!data.full_name) {
        errors.full_name = 'Full name is required.';
    } else if (data.full_name.length < 2) {
        errors.full_name = 'Your name must be at least 2 characters.';
    }
    if (!data.email) {
        errors.email = 'Email is required.';
    } else if (!isValidEmail(data.email)) {
        errors.email = 'Enter a valid email address.';
    }
    if (!data.password) {
        errors.password = 'Password is required.';
    } else if (data.password.length < 8) {
        errors.password = 'Password must be at least 8 characters.';
    }
    if (!data.confirm_password) {
        errors.confirm_password = 'Please confirm your password.';
    } else if (data.password !== data.confirm_password) {
        errors.confirm_password = 'Passwords do not match.';
    }
    return errors;
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function displayErrors(errors, form) {
    for (const field in errors) {
        const input = form.querySelector('[name="' + field + '"]');
        if (input) {
            input.setAttribute('aria-invalid', 'true');
            const errorId = field + '_error';
            input.setAttribute('aria-describedby', errorId);

            const errorEl = document.createElement('div');
            errorEl.className = 'form-error';
            errorEl.id = errorId;
            errorEl.textContent = errors[field];
            input.parentNode.insertBefore(errorEl, input.nextSibling);
        }
    }
}

function clearFormErrors(form) {
    form.querySelectorAll('.form-error').forEach(function(el) {
        el.remove();
    });
    form.querySelectorAll('[aria-invalid="true"]').forEach(function(input) {
        input.removeAttribute('aria-invalid');
        input.removeAttribute('aria-describedby');
    });
}

function showAuthMessage(message, type = 'info') {
    if (!authMessage) {
        displayAlert(message, type);
        return;
    }
    authMessage.textContent = message;
    authMessage.className = 'alert alert-' + type;
    authMessage.classList.remove('visually-hidden');
}

function hideAuthMessage() {
    if (authMessage) {
        authMessage.className = 'alert visually-hidden';
        authMessage.textContent = '';
    }
}

function displayAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + type;
    alert.textContent = message;
    const container = document.querySelector('.auth-card');
    if (container) {
        container.insertBefore(alert, container.firstChild);
    }
    setTimeout(function() {
        alert.remove();
    }, 5000);
}

function displayFlashMessages() {
    const flashElement = document.getElementById('flashMessage');
    if (flashElement) {
        const message = flashElement.dataset.message;
        const type = flashElement.dataset.type || 'info';
        if (message) {
            showAuthMessage(message, type);
        }
    }
}


