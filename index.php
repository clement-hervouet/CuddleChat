<?php
// Initialize session
session_start();

if (empty($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
	header('location: login/connections/login.php');
	exit;
} else {
    header('location: home.php');
    exit;
}
?>