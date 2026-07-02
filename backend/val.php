<?php
session_start();
require_once 'db.php';
$database = $db ?? $pdo;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';

// Clear old system feedback notices
unset($_SESSION['auth_errors']);
unset($_SESSION['auth_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================================
    // BLOCK 1: ADMIN SIGN UP
    // ==========================================
    if (isset($_POST['signup_submit'])) {
        $fullname         = trim($_POST['fullname'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms_accepted   = isset($_POST['terms_agreement']);

        $errors = [];
        if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = "All registration fields are strictly required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email format.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        if (!$terms_accepted) {
            $errors[] = "You must agree to the Terms & Privacy Rules.";
        }

        if (count($errors) === 0) {
            try {
                $stmt = $database->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = "This email is already registered.";
                }
            } catch (PDOException $e) {
                $errors[] = "Database check error: " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            $_SESSION['auth_errors'] = $errors;
            header("Location: ../page/signup.php");
            exit();
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Dynamic rule: Check if this is the first admin (make them primary_admin)
                $countStmt = $database->prepare("SELECT COUNT(*) FROM admins");
                $countStmt->execute();
                $adminCount = (int) $countStmt->fetchColumn();
                $role = $adminCount === 0 ? 'primary_admin' : 'editor';

                $insert_stmt = $database->prepare(
                    "INSERT INTO admins (fullname, email, role, password) VALUES (:fullname, :email, :role, :password)"
                );
                $insert_stmt->execute([
                    'fullname' => $fullname,
                    'email'    => $email,
                    'role'     => $role,
                    'password' => $hashed_password,
                ]);

                $_SESSION['auth_success'] = "Registration complete! You can now sign in.";
                header("Location: ../page/login.php");
                exit();
            } catch (PDOException $e) {
                $_SESSION['auth_errors'] = ["Database insertion failed: " . $e->getMessage()];
                header("Location: ../page/signup.php");
                exit();
            }
        }
    }

    // ==========================================
    // BLOCK 2: ADMIN LOGIN
    // ==========================================
    if (isset($_POST['login_submit'])) {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['auth_errors'] = ["Both email and password fields are mandatory."];
            header("Location: ../page/login.php");
            exit();
        }

        try {
            $stmt = $database->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $adminId = (int)$admin['id'];
                $status = ($adminId === 1 || $adminId === 2) ? 'Active' : ($admin['status'] ?? 'Active');
                if ($status !== 'Active') {
                    $_SESSION['auth_errors'] = ["Your account has been suspended. Please contact support."];
                    header("Location: ../page/login.php");
                    exit();
                }

                $_SESSION['admin_id']         = $adminId;
                $_SESSION['admin_name']       = $admin['fullname'];
                $_SESSION['admin_logged_in']  = true;
                $_SESSION['admin_role']       = ($adminId === 1 || $adminId === 2) ? 'primary_admin' : ($admin['role'] ?? 'editor');
                $_SESSION['last_activity']    = time();

                header("Location: ../page/index.php");
                exit();
            } else {
                $_SESSION['auth_errors'] = ["Invalid email or password combination."];
                header("Location: ../page/login.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Authentication system failure: " . $e->getMessage()];
            header("Location: ../page/login.php");
            exit();
        }
    }

    // ==========================================
    // BLOCK 3: FORGOT PASSWORD (PHPMailer)
    // ==========================================
    if (isset($_POST['forgot_submit'])) {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['auth_errors'] = ["Please enter a valid email address structure."];
            header("Location: ../page/forgot-password.php");
            exit();
        }

        try {
            $stmt = $database->prepare("SELECT id, fullname FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $token  = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                $update = $database->prepare(
                    "UPDATE admins SET reset_token = :token, token_expire = :expiry WHERE email = :email"
                );
                $update->execute(['token' => $token, 'expiry' => $expiry, 'email' => $email]);

                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../page/reset-password.php?token=" . $token;

                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host       = 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '1b38bb6e0f9eb2';
                    $mail->Password   = 'c192302ace36ed';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 2525;

                    $mail->setFrom('security@adminhub.com', 'AdminHub Security');
                    $mail->addAddress($email, $admin['fullname']);

                    $mail->isHTML(true);
                    $mail->Subject = 'AdminHub Account Access Recovery';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 500px; padding: 30px; background-color: #161e2e; color: #ffffff; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);'>
                            <h2 style='color: #3b82f6; margin-top: 0;'>Password Reset Request</h2>
                            <p style='color: #9ca3af;'>Hello " . htmlspecialchars($admin['fullname']) . ",</p>
                            <p style='color: #9ca3af;'>We received a request to modify your security access credentials on AdminHub. Click the action button below to configure your new profile password:</p>
                            <p style='margin: 30px 0; text-align: center;'>
                                <a href='{$reset_link}' style='background-color: #3b82f6; color: white; padding: 12px 28px; text-decoration: none; font-weight: 600; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(59,130,246,0.3);'>Reset Password</a>
                            </p>
                            <p style='font-size: 12px; color: #6b7280; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;'>This link will auto-expire in 30 minutes. If you did not make this request, you can safely ignore this email.</p>
                        </div>
                    ";

                    $mail->send();
                    $_SESSION['auth_success'] = "A secure reset link has been dispatched to your email.";
                } catch (Exception $e) {
                    $_SESSION['auth_errors'] = ["Mail delivery system failure: {$mail->ErrorInfo}"];
                }
            } else {
                $_SESSION['auth_success'] = "A secure reset link has been dispatched to your email.";
            }

            header("Location: ../page/forgot-password.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Recovery System Error: " . $e->getMessage()];
            header("Location: ../page/forgot-password.php");
            exit();
        }
    }

    // ==========================================
    // BLOCK 4: PASSWORD RESET UPDATE
    // ==========================================
    if (isset($_POST['reset_submit'])) {
        $token            = $_POST['token'] ?? '';
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $_SESSION['auth_errors'] = ["Your new password must be at least 8 characters long."];
            header("Location: ../page/reset-password.php?token=" . urlencode($token));
            exit();
        }
        if ($password !== $confirm_password) {
            $_SESSION['auth_errors'] = ["Passwords entries do not match."];
            header("Location: ../page/reset-password.php?token=" . urlencode($token));
            exit();
        }

        try {
            $stmt = $database->prepare(
                "SELECT id FROM admins WHERE reset_token = :token AND token_expire > NOW() LIMIT 1"
            );
            $stmt->execute(['token' => $token]);
            $admin = $stmt->fetch();

            if ($admin) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                $update   = $database->prepare(
                    "UPDATE admins SET password = :password, reset_token = NULL, token_expire = NULL WHERE id = :id"
                );
                $update->execute(['password' => $new_hash, 'id' => $admin['id']]);

                $_SESSION['auth_success'] = "Security credentials updated! Please sign in with your new password.";
                header("Location: ../page/login.php");
                exit();
            } else {
                $_SESSION['auth_errors'] = ["Authorization context expired. Please request a new token link."];
                header("Location: ../page/forgot-password.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Critical Reset Execution Failure: " . $e->getMessage()];
            header("Location: ../page/forgot-password.php");
            exit();
        }
    }

    // ==========================================
    // BLOCK 5: CUSTOMER REGISTRATION
    // ==========================================
    if (isset($_POST['customer_signup_submit'])) {
        $fullname         = trim($_POST['fullname'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $terms_accepted   = isset($_POST['terms_agreement']);

        $errors = [];
        if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
            $errors[] = "All fields are required.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please provide a valid email address.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        if (!$terms_accepted) {
            $errors[] = "You must agree to the Terms & Conditions.";
        }

        if (empty($errors)) {
            try {
                $stmt = $database->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $errors[] = "An account with that email already exists.";
                }
            } catch (PDOException $e) {
                $errors[] = "Registration check failed.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['auth_errors'] = $errors;
            header("Location: ../customer/register.php");
            exit();
        }

        try {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt   = $database->prepare(
                "INSERT INTO customers (fullname, email, password_hash) VALUES (:fullname, :email, :password_hash)"
            );
            // Correction: Array bind key perfectly mirrors query variable signature now
            $stmt->execute(['fullname' => $fullname, 'email' => $email, 'password_hash' => $hashed]);

            $_SESSION['auth_success'] = "Account created! Please sign in.";
            header("Location: ../customer/login.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Registration failed. Please try again."];
            header("Location: ../customer/register.php");
            exit();
        }
    }

    // ==========================================
    // BLOCK 6: CUSTOMER LOGIN
    // ==========================================
    if (isset($_POST['customer_login_submit'])) {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['auth_errors'] = ["Email and password are required."];
            header("Location: ../customer/login.php");
            exit();
        }

        try {
            $stmt = $database->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password_hash'])) {
                if (!(bool) $customer['is_active']) {
                    $_SESSION['auth_errors'] = ["Your account has been deactivated. Please contact support."];
                    header("Location: ../customer/login.php");
                    exit();
                }

                $_SESSION['customer_id']            = $customer['id'];
                $_SESSION['customer_name']         = $customer['fullname'];
                $_SESSION['customer_logged_in']    = true;
                $_SESSION['customer_last_activity'] = time();

                $database->prepare("UPDATE customers SET last_login_at = NOW() WHERE id = :id")
                    ->execute(['id' => $customer['id']]);

                header("Location: ../customer/index.php");
                exit();
            } else {
                $_SESSION['auth_errors'] = ["Invalid email or password."];
                header("Location: ../customer/login.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['auth_errors'] = ["Login system error. Please try again."];
            header("Location: ../customer/login.php");
            exit();
        }
    }

} else {
    header("Location: ../page/login.php");
    exit();
}