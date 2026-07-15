<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header('Location: shop.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $database->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $customer = $stmt->fetch();

        if ($customer && (int)$customer['is_active'] === 1 && password_verify($password, $customer['password_hash'])) {
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['fullname'];

            header('Location: shop.php');
            exit();
        } else {
            $error = 'Invalid email, password, or account is deactivated.';
        }
    } else {
        $error = 'Please fill out all fields.';
    }
}
$flashMessages = consume_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to your <?php echo e(app_setting('website_name', 'AdminHub')); ?> customer account.">
    <title>Customer Login &middot; <?php echo e(app_setting('website_name', 'AdminHub')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <main class="auth-card-box" role="main">
        <div class="auth-brand mb-4">
            <span class="auth-logo-mark"><i class="bi bi-bag-heart-fill"></i></span>
            <?php echo e(app_setting('website_name', 'AdminHub')); ?>
        </div>

        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Sign in to continue to your account.</p>

        <?php foreach ($flashMessages as $msg): ?>
            <div class="alert alert-<?php echo e($msg['type']); ?> py-2"><?php echo e($msg['message']); ?></div>
        <?php endforeach; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="field">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="you@example.com" required autocomplete="username">
            </div>

            <div class="field">
                <label class="form-label" for="password">Password</label>
                <div class="pw-field">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="auth-row">
                <div class="form-check mb-0">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                    <label class="form-check-label text-secondary" for="rememberMe">Remember Me</label>
                </div>
                <a href="forgot-password.php" class="auth-foot" style="margin:0;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-brand">Sign In</button>
        </form>

        <div class="auth-foot">
            Don't have an account? <a href="register.php">Create Customer Account</a>
        </div>
    </main>

    <script>
        document.querySelectorAll('.pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>

</body>
</html>
