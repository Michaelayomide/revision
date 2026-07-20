<?php
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header('Location: shop.php');
    exit();
}
$siteName = app_setting('website_name', 'AdminHub');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recover your <?php echo e($siteName); ?> customer account password.">
    <title>Forgot Password &middot; <?php echo e($siteName); ?></title>
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
            <?php echo e($siteName); ?>
        </div>

        <h1 class="auth-title">Forgot Password</h1>
        <p class="auth-subtitle">Account recovery for customer accounts is not enabled in this environment.</p>

        <div class="alert py-3 d-flex align-items-start gap-2" style="background: var(--brand-light); border: 1px solid rgba(16,185,129,0.25); color: var(--brand-dark); border-radius: 8px;">
            <i class="bi bi-info-circle fs-5"></i>
            <div>
                If you need to reset your password, please contact support or use the
                <a href="login.php" style="color: var(--brand-dark); font-weight: 600;">customer login</a>
                once recovery is available.
            </div>
        </div>

        <a href="login.php" class="btn-brand mt-2"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>

        <div class="auth-foot">
            Remembered it? <a href="login.php">Sign in</a>
        </div>
    </main>

</body>
</html>
