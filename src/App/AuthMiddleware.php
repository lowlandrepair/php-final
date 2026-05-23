<?php

class AuthMiddleware
{
    public static function isPublicRoute(string $route): bool
    {
        return in_array($route, ['login', 'register'], true);
    }

    public static function requireLogin(): void
    {
        if (!isLoggedIn()) {
            header('Location: /index.php?route=login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
            header('Location: /index.php?route=login');
            exit;
        }
    }
}
