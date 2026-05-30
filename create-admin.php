<?php
// Admin account creation script
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/App/User.php';

try {
    $user = new User();
    
    // Create admin account
    $email = 'admin@crimemap.local';
    $password = 'Admin@2026';
    $fullName = 'Administrator';
    
    $userId = $user->create($email, $password, $fullName, 'admin');
    
    if ($userId) {
        echo "✅ Admin account created successfully!\n\n";
        echo "Login credentials:\n";
        echo "Email: " . $email . "\n";
        echo "Password: " . $password . "\n";
        echo "Role: Admin\n\n";
        echo "You can now log in at: http://localhost/php-final/public/index.php?route=login\n";
    } else {
        echo "❌ Failed to create admin account. The email might already exist.\n";
        echo "To use existing admin account, use:\n";
        echo "Email: admin@crimemap.local\n";
        echo "Password: Admin@2026\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
