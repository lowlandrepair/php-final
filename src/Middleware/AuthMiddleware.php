<?php

class AuthMiddleware
{
    public static function isPublicRoute(string $route): bool
    {
        return in_array($route, ['login', 'register'], true);
    }

    public function execute(): void
    {
        if (!isLoggedIn()) {
            header('Location: /php-final/public/index.php?route=login');
            exit;
        }
    }

    public static function requireLogin(): void
    {
        if (!isLoggedIn()) {
            header('Location: /php-final/public/index.php?route=login');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        if (!isLoggedIn() || getCurrentUserRole() !== 'admin') {
            header('Location: /php-final/public/index.php?route=login');
            exit;
        }
    }
}
