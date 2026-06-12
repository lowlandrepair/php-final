<?php
require_once 'config.php';

if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin/dashboard.php");
        exit;
    }

    header("Location: map.php");
    exit;
}

header("Location: auth/login.php");
exit;
?>
