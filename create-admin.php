<?php
// Admin account creation script
require_once 'config.php';

try {
    $email = 'admin@crimemap.local';
    $password = 'Admin@2026';
    $fullName = 'Administrator';
    
    $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    
    if ($stmt->fetch()) {
        echo "❌ Failed to create admin account. The email might already exist.\n";
        echo "To use existing admin account, use:\n";
        echo "Email: admin@crimemap.local\n";
        echo "Password: Admin@2026\n";
    } else {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $sql = "INSERT INTO users (email, password_hash, full_name, role) VALUES (:email, :password_hash, :full_name, 'admin')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':full_name' => $fullName
        ]);
        
        echo "✅ Admin account created successfully!\n\n";
        echo "Login credentials:\n";
        echo "Email: " . $email . "\n";
        echo "Password: " . $password . "\n";
        echo "Role: Admin\n\n";
        echo "You can now log in at: http://localhost/php-final/auth/login.php\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
