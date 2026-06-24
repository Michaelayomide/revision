<?php
session_start();
require_once 'db.php';
$database = $db ?? $pdo;

// Safe fallback check for missing login variables
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../page/login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================================
    // ACTION A: UPDATE ACCOUNT PROFILE DATA
    // ==========================================
    if (isset($_POST['profile_update_submit'])) {
        $fullname = trim($_POST['update_fullname'] ?? '');
        $email    = trim($_POST['update_email'] ?? '');

        if (empty($fullname) || empty($email)) {
            $_SESSION['auth_errors'] = ["Profile name and email cannot be empty entries."];
            header("Location: ../page/settings.php");
            exit();
        }

        try {
            // Check if email is already in use by another admin
            $check_email = $database->prepare("SELECT id FROM admins WHERE email = :email AND id != :id LIMIT 1");
            $check_email->execute(['email' => $email, 'id' => $admin_id]);
            
            if ($check_email->fetch()) {
                $_SESSION['auth_errors'] = ["This email address is already in use by another account."];
                header("Location: ../page/settings.php");
                exit();
            }

            // Apply updates
            $update = $database->prepare("UPDATE admins SET fullname = :fullname, email = :email WHERE id = :id");
            $update->execute(['fullname' => $fullname, 'email' => $email, 'id' => $admin_id]);

            // Sync structural session data dynamically
            $_SESSION['admin_name'] = $fullname;

            $_SESSION['auth_success'] = "Profile details updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Profile update database error: " . $e->getMessage()];
        }

        header("Location: ../page/settings.php");
        exit();
    }

    // ==========================================
    // ACTION B: PASSWORD ALTERATION SECURITY CHECK
    // ==========================================
    if (isset($_POST['security_update_submit'])) {
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass     = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_new_password'] ?? '';

        if (strlen($new_pass) < 8) {
            $_SESSION['auth_errors'] = ["Your new password choice must be at least 8 characters long."];
            header("Location: ../page/settings.php");
            exit();
        }

        if ($new_pass !== $confirm_pass) {
            $_SESSION['auth_errors'] = ["The new password entry confirmation fields do not match."];
            header("Location: ../page/settings.php");
            exit();
        }

        try {
            // Verify current password match
            $stmt = $database->prepare("SELECT password FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($current_pass, $admin['password'])) {
                // Update with secure modern BCRYPT hashing structure
                $hashed_new_password = password_hash($new_pass, PASSWORD_BCRYPT);
                
                $update_pass = $database->prepare("UPDATE admins SET password = :password WHERE id = :id");
                $update_pass->execute(['password' => $hashed_new_password, 'id' => $admin_id]);

                $_SESSION['auth_success'] = "Security access password changed successfully!";
            } else {
                $_SESSION['auth_errors'] = ["The current validation password you provided is invalid."];
            }
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Security database runtime error: " . $e->getMessage()];
        }

        header("Location: ../page/settings.php");
        exit();
    }

} else {
    header("Location: ../page/settings.php");
    exit();
}