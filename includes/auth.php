<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: ../login/connections/login.php');
    exit;
}

define('CURRENT_USER_ID',  (int) $_SESSION['id_user']);
define('CURRENT_USERNAME', htmlspecialchars($_SESSION['username'] ?? 'Peluche inconnue', ENT_QUOTES, 'UTF-8'));
?>