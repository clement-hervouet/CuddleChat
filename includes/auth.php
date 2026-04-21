<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /login/login.php');
    exit;
}

define('CURRENT_USER_ID',   (int) $_SESSION['user_id']);
define('CURRENT_USERNAME',  htmlspecialchars($_SESSION['username'] ?? 'Peluche inconnue', ENT_QUOTES, 'UTF-8'));
