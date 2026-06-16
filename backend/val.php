<?php
session_start();
require_once 'db.php';
$database = $db ?? $pdo; // Safety mapping driver for database connection

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Core Composer Autoloader - Loads all PHPMailer classes automatically
require_once '../vendor/autoload.php';

// Clear old system feedback notices to prevent duplicate alerts
unset($_SESSION['auth_errors']);
unset($_SESSION['auth_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================================
    // BLOCK 1: SIGN UP VALIDATION
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
                $insert_stmt = $database->prepare("INSERT INTO admins (fullname, email, password) VALUES (:fullname, :email, :password)");
                $insert_stmt->execute(['fullname' => $fullname, 'email' => $email, 'password' => $hashed_password]);

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
    // BLOCK 2: LOGIN VALIDATION
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
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['fullname'];
                $_SESSION['admin_logged_in'] = true;
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
    // BLOCK 3: FORGOT PASSWORD GENERATION (PHPMailer + Mailtrap)
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
                // Generate unique secure token and a 30-minute expiration timestamp
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

                $update = $database->prepare("UPDATE admins SET reset_token = :token, token_expire = :expiry WHERE email = :email");
                $update->execute(['token' => $token, 'expiry' => $expiry, 'email' => $email]);

                // Constructs the dynamic reset URL pointer
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/../page/reset-password.php?token=" . $token;
                
                $mail = new PHPMailer(true);

                try {
                    // Mailtrap Sandbox SMTP Configurations
                    $mail->isSMTP();
                    $mail->Host       = 'sandbox.smtp.mailtrap.io';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '1b38bb6e0f9eb2';   // 👈 Paste your Mailtrap user ID token here
                    $mail->Password   = 'c192302ace36ed';   // 👈 Paste your Mailtrap password token here
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
                    $mail->Port       = 2525;

                    // Message Header Configuration
                    $mail->setFrom('security@adminhub.com', 'AdminHub Security');
                    $mail->addAddress($email, $admin['fullname']);

                    // HTML Content Email Design (Matches Dark Aesthetic)
                    $mail->isHTML(true);
                    $mail->Subject = 'AdminHub Account Access Recovery';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 500px; padding: 30px; background-color: #161e2e; color: #ffffff; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);'>
                            <h2 style='color: #3b82f6; margin-top: 0;'>Password Reset Request</h2>
                            <p style='color: #9ca3af;'>Hello " . htmlspecialchars($admin['fullname']) . ",</p>
                            <p style='color: #9ca3af;'>We received a request to modify your security access credentials on AdminHub. Click the action button below to configure your new profile password:</p>
                            <p style='margin: 30px 0; text-align: center;'>
                                <a href='{$reset_link}' style='background-color: #3b82f6; color: white; padding: 12px 28px; text-decoration: none; font-weight: 600; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(59,130,246,0.3);'>Reset Password</a>
                            </p>
                            <p style='font-size: 12px; color: #6b7280; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 15px;'>This link session will auto-expire in 30 minutes. If you did not make this request, you can safely ignore this email.</p>
                        </div>
                    ";

                    $mail->send();
                    $_SESSION['auth_success'] = "A secure reset link has been dispatched to your email.";
                } catch (Exception $e) {
                    $_SESSION['auth_errors'] = ["Mail delivery system failure: {$mail->ErrorInfo}"];
                }
            } else {
                // Security Design Pattern: Don't reveal valid/invalid emails to prevent user enumeration
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
    // BLOCK 4: PASSWORD RESET UPDATE OVERWRITE
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
            // Confirm the token is matchable and within the 30-minute lifespan window
            $stmt = $database->prepare("SELECT id FROM admins WHERE reset_token = :token AND token_expire > NOW() LIMIT 1");
            $stmt->execute(['token' => $token]);
            $admin = $stmt->fetch();

            if ($admin) {
                $new_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // Commit new hash and clear temporary verification tokens
                $update = $database->prepare("UPDATE admins SET password = :password, reset_token = NULL, token_expire = NULL WHERE id = :id");
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

} else {
    // Redirect direct URL tampering attempts back to login
    header("Location: ../page/login.php");
    exit();
}