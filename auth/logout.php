<?php
require_once '../config.php';

$_SESSION = [];
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

header("Location: login.php");
exit;
?>
