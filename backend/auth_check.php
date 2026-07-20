<?php
// Secure session initialization if not already started by the parent page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin login session variable is missing or false
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Save an error notice to show on the login page
    $_SESSION['auth_errors'] = ["Access Denied. Please log in to access the administration hub."];
    
    // Kick them out to the login screen
    header("Location: login.php");
    exit();
}