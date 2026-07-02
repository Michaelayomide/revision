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
    <title>Create Account — <?php echo e(app_setting('website_name','AdminHub')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <div class="auth-wrapper" style="display:flex; align-items:center; justify-content:center; width:100%;">
        <div class="auth-card" id="authCard">

            <!-- Brand Panel -->
            <div class="auth-brand-panel text-white">
                <div class="brand-logo">
                    <i class="bi bi-shop-window"></i>
                    <?php echo e(app_setting('website_name','AdminHub')); ?>
                </div>
                <div class="my-auto py-4">
                    <h2 class="fw-bold mb-3" style="letter-spacing:-0.5px;">Join Us Today!</h2>
                    <p class="text-white-50 m-0" style="line-height:1.6; font-size:0.95rem;">
                        Create your free account to browse products, track orders, and enjoy a seamless shopping experience.
                    </p>
                    <ul class="list-unstyled mt-4" style="font-size:0.88rem;">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Free account creation</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Real-time order tracking</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Order history & receipts</li>
                    </ul>
                </div>
                <div class="text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo e(app_setting('website_name','AdminHub')); ?></div>
            </div>

            <!-- Form Panel -->
            <div class="auth-form-container">
                <button type="button" class="panel-toggle-btn" onclick="document.getElementById('authCard').classList.toggle('panel-collapsed')" title="Toggle">
                    <i class="bi bi-arrows-left-right"></i>
                </button>

                <div class="mb-4">
                    <h2 class="fw-bold mb-1" style="letter-spacing:-0.5px; color:#1e293b;">Create Account</h2>
                    <p class="text-muted" style="font-size:0.9rem;">Fill in your details to get started.</p>
                </div>

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
                        <label class="form-label small fw-semibold text-secondary" for="regFullname">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="regFullname" name="fullname" placeholder="John Doe" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary" for="regEmail">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="regEmail" name="email" placeholder="you@example.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary" for="regPassword">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="regPassword" name="password" placeholder="Minimum 8 characters" required minlength="8">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary" for="regConfirm">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-shield-lock"></i></span>
                            <input type="password" class="form-control" id="regConfirm" name="confirm_password" placeholder="Re-enter password" required>
                        </div>
                    </div>
                    <div class="mb-4 form-check">
                        <input class="form-check-input" type="checkbox" id="termsCheck" name="terms_agreement" required>
                        <label class="form-check-label text-muted small" for="termsCheck">
                            I agree to the <a href="#" class="text-link-green">Terms & Conditions</a>
                        </label>
                    </div>
                    <button type="submit" name="customer_signup_submit" class="btn btn-submit-action w-100 text-white shadow-sm mb-3">
                        Create My Account <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <div class="text-center small">
                        <span class="text-muted">Already have an account?</span>
                        <a href="login.php" class="text-link-green ms-1">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
