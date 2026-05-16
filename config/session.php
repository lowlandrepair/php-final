<?php

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function setFlashMessage(string $message, string $type = 'info'): void
{
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type,
    ];
}

function hasFlashMessage(): bool
{
    return !empty($_SESSION['flash_message']);
}

function getFlashMessage(): ?array
{
    if (!hasFlashMessage()) {
        return null;
    }

    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $message;
}

function loginUser(array $user): void
{
    $_SESSION['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'role' => $user['role'],
    ];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user']);
}

function getCurrentUserRole(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function getCurrentUserId(): ?int
{
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}
