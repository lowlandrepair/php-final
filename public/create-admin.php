<?php
require_once 'config.php';

$email = 'admin@crimemap.local';
$password = 'Admin@2026';
$fullName = 'Administrator';

try {
    $sql = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        echo "Admin account already exists.\n";
        echo "Email: " . $email . "\n";
        echo "Password: " . $password . "\n";
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $sql = "INSERT INTO users (email, password_hash, full_name, role) VALUES (:email, :password_hash, :full_name, 'admin')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':full_name' => $fullName
    ]);

    echo "Admin account created successfully.\n\n";
    echo "Email: " . $email . "\n";
    echo "Password: " . $password . "\n";
    echo "Role: Admin\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
