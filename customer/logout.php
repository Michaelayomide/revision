<?php
// customer/logout.php
declare(strict_types=1);

// Ensure a session exists before clearing it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset explicit scope variables
$_SESSION = [];

// Obliterate the session cookie tracking structure
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy backend context completely
session_destroy();

// Safely redirect execution flow
header('Location: login.php');
exit();