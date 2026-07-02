<?php
session_start();
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    redirect_to('../customer/index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login — <?php echo e(app_setting('website_name','AdminHub')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div style="display:flex; align-items:center; justify-content:center; width:100%;">
        <div class="auth-card" id="authCard">

            <!-- Brand Panel -->
            <div class="auth-brand-panel text-white">
                <div class="brand-logo">
                    <i class="bi bi-shop-window"></i>
                    <?php echo e(app_setting('website_name','AdminHub')); ?>
                </div>
                <div class="my-auto py-4">
                    <h2 class="fw-bold mb-3" style="letter-spacing:-0.5px;">Welcome Back!</h2>
                    <p class="text-white-50 m-0" style="line-height:1.6; font-size:0.95rem;">
                        Sign in to your account to view your orders, track deliveries, and continue shopping.
                    </p>
                </div>
                <div class="text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo e(app_setting('website_name','AdminHub')); ?></div>
            </div>

            <!-- Form Panel -->
            <div class="auth-form-container">
                <button type="button" class="panel-toggle-btn" onclick="document.getElementById('authCard').classList.toggle('panel-collapsed')" title="Toggle">
                    <i class="bi bi-arrows-left-right"></i>
                </button>

                <div class="mb-4">
                    <h2 class="fw-bold mb-1" style="letter-spacing:-0.5px; color:#1e293b;">Sign In</h2>
                    <p class="text-muted" style="font-size:0.9rem;">Enter your email and password to access your account.</p>
                </div>

                <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                    <div class="alert alert-warning py-2 mb-3 small" style="border-radius:10px;">
                        <i class="bi bi-clock me-2"></i>Your session expired due to inactivity.
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['auth_errors'])): ?>
                    <div class="alert alert-danger py-2 mb-3" style="font-size:0.88rem; border-radius:10px;">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($_SESSION['auth_errors'] as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; unset($_SESSION['auth_errors']); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['auth_success'])): ?>
                    <div class="alert alert-success py-2 small mb-3" style="border-radius:10px;">
                        <i class="bi bi-check-circle me-2"></i><?php echo e($_SESSION['auth_success']); unset($_SESSION['auth_success']); ?>
                    </div>
                <?php endif; ?>

                <form action="../backend/val.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary" for="loginEmail">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="loginEmail" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-semibold text-secondary" for="loginPassword">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="loginPassword" name="password" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" name="customer_login_submit" class="btn btn-submit-action w-100 text-white shadow-sm mb-3">
                        Sign In <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <div class="text-center small">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="register.php" class="text-link-green ms-1">Create Account</a>
                    </div>
                </form>

                <hr class="my-4">
                <div class="text-center">
                    <a href="../page/login.php" class="text-muted small">
                        <i class="bi bi-shield-lock me-1"></i>Admin / Staff Login
                    </a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
