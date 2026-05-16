<?php

require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    public function login(array $data): array
    {
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        loginUser($user);

        $redirect = $user['role'] === 'admin' ? 'dashboard' : 'map';

        return [
            'success' => true,
            'redirect' => $redirect,
        ];
    }

    public function register(array $data): array
    {
        $fullName = trim($data['full_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        $userModel = new User();
        $userId = $userModel->create($email, $password, $fullName);

        if ($userId === false) {
            return ['success' => false, 'message' => 'Unable to register. Email may already be in use or password is too weak.'];
        }

        $user = $userModel->findByEmail($email);
        if ($user === false) {
            return ['success' => false, 'message' => 'Registration succeeded but user lookup failed.'];
        }

        loginUser($user);

        return [
            'success' => true,
            'redirect' => 'map',
        ];
    }

    public function logout(): array
    {
        logoutUser();
        return ['success' => true, 'message' => 'You have been logged out.'];
    }
}
