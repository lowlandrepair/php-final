<?php
require_once 'config.php';

if (isset($_SESSION['user'])) {
    $redirect = $_SESSION['user']['role'] === 'admin' ? 'admin/dashboard.php' : 'map.php';
    header("Location: $redirect");
    exit;
} else {
    header("Location: auth/login.php");
    exit;
}
?>
