<?php
session_start();
// Import your database connection
require_once 'db.php';

// Wipe out old alerts before processing a new click
unset($_SESSION['auth_errors']);
unset($_SESSION['auth_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================================
    // BLOCK 1: SIGN UP VALIDATION (Targeting table 'admins')
    // ==========================================
    if (isset($_POST['signup_submit'])) {
        
        // Grabbed names matched perfectly to your clean HTML inputs
        $fullname         = trim($_POST['fullname']);
        $email            = trim($_POST['email']);
        $password         = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $terms_accepted   = isset($_POST['terms_agreement']) ? true : false;

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

        // Duplicate email safety check in 'admins' table
        if (count($errors) === 0) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
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
            header("Location: ../signup.php"); // Bounce out of backend folder back to signup
            exit();
        } else {
            try {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Matches your exact database column names: fullname, email, password
                $insert_stmt = $pdo->prepare("INSERT INTO admins (fullname, email, password) VALUES (:fullname, :email, :password)");
                $insert_stmt->execute([
                    'fullname' => $fullname,
                    'email'    => $email,
                    'password' => $hashed_password
                ]);

                $_SESSION['auth_success'] = "Registration complete! You can now sign in.";
                header("Location: ../login.php"); 
                exit();

            } catch (PDOException $e) {
                $_SESSION['auth_errors'] = ["Database insertion failed: " . $e->getMessage()];
                header("Location: ../signup.php");
                exit();
            }
        }
    }

    // ==========================================
    // BLOCK 2: LOGIN VALIDATION (Targeting table 'admins')
    // ==========================================
    if (isset($_POST['login_submit'])) {
        
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        $errors = [];

        if (empty($email) || empty($password)) {
            $errors[] = "Both email and password fields are mandatory.";
        }

        if (count($errors) > 0) {
            $_SESSION['auth_errors'] = $errors;
            header("Location: ../login.php");
            exit();
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = :email LIMIT 1");
                $stmt->execute(['email' => $email]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_name'] = $admin['fullname'];
                    $_SESSION['admin_logged_in'] = true;

                    header("Location: ../index.php"); 
                    exit();
                } else {
                    $_SESSION['auth_errors'] = ["Invalid email or password combination."];
                    header("Location: ../login.php");
                    exit();
                }

            } catch (PDOException $e) {
                $_SESSION['auth_errors'] = ["Authentication system failure: " . $e->getMessage()];
                header("Location: ../login.php");
                exit();
            }
        }
    }

       

} else {
    header("Location: ../login.php");
    exit();
}
?>