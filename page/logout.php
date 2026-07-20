<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie in the browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the server session file
session_destroy();

// Redirect safely using clean URL parameters
if (isset($_GET['reason']) && $_GET['reason'] === 'timeout') {
    header("Location: login.php?reason=timeout");
} else {
    header("Location: login.php?status=loggedout");
}
exit();
?>