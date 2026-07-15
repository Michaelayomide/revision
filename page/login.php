<?php 
session_start(); 

// If an admin is ALREADY logged in, send them straight to the dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: ../page/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to the AdminHub operations dashboard.">
    <title>AdminHub - Secure Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

    <div class="auth-wrapper">
        <div class="auth-card">

            <div class="auth-brand-panel">
                <div class="brand-logo">
                    <i class="fa-solid fa-layer-group"></i> AdminHub
                </div>
                <div>
                    <h2 class="auth-hero-title">Welcome Back!</h2>
                    <p class="auth-hero-text">
                        Manage your operations, track live metrics, and configure system
                        parameters from one secure workspace.
                    </p>
                    <div class="auth-feature"><i class="fa-solid fa-chart-line"></i> Real-time sales & inventory insights</div>
                    <div class="auth-feature"><i class="fa-solid fa-shield-halved"></i> Role-based access control</div>
                    <div class="auth-feature"><i class="fa-solid fa-bell"></i> Order & low-stock alerts</div>
                </div>
                <div class="auth-copyright">&copy; 2026 AdminHub. All rights reserved.</div>
            </div>

            <div class="auth-form-container">
                <div class="auth-title">Sign In</div>
                <div class="auth-subtitle">Enter your verified credentials to continue.</div>

                <form action="../backend/val.php" method="POST" novalidate>

                    <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
                        <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <div>For your security, you were logged out due to inactivity.</div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['status']) && $_GET['status'] === 'loggedout'): ?>
                        <div class="alert alert-success py-2 small mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i> You have been safely logged out.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['auth_errors'])): ?>
                        <div class="alert alert-danger py-2 mb-3" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['auth_errors'] as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['auth_errors']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['auth_success'])): ?>
                        <div class="alert alert-success py-2 small mb-3 d-flex align-items-center">
                            <i class="bi bi-check-circle me-2"></i><?php echo $_SESSION['auth_success']; ?>
                        </div>
                        <?php unset($_SESSION['auth_success']); ?>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="username@domain.com" required autocomplete="username">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: var(--fs-body);">
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label text-muted" for="rememberMe">Remember this device</label>
                        </div>
                        <a href="forgot-password.php" class="text-link-brand">Forgot Password?</a>
                    </div>

                    <button type="submit" name="login_submit" class="btn btn-submit-action w-100 mb-3">
                        Sign In to Dashboard <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>

                    <div class="text-center w-100" style="font-size: var(--fs-body);">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="signup.php" class="text-link-brand ms-1">Create Account</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

</body>
</html>
