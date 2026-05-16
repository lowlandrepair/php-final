<?php

define('APP_NAME', 'Crime Map Simulator');
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 10);

$host = 'localhost';
$dbname = 'Crime-map';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

session_start();

?>


