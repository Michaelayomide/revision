<?php
require_once __DIR__ . '/config/init.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    redirect_to('page/index.php');
}

redirect_to('page/login.php');
