<?php
require_once 'includes/functions.php';

// Log the logout activity
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'logout', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?>
