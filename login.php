<?php 
// Start the session to catch standard validation data from backend/val.php
session_start(); 

// If an admin is ALREADY logged in, send them straight to the dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Secure Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

    <div class="auth-wrapper">
        <div class="auth-card" id="authCard">
            
            <div class="auth-brand-panel text-white">
                <div class="brand-logo">
                    <i class="fa-solid fa-square-poll-horizontal me-2"></i>AdminHub
                </div>
                <div class="my-auto py-4">
                    <h2 class="fw-bold mb-3" style="letter-spacing: -0.5px;">Welcome Back!</h2>
                    <p class="text-white-50 m-0" style="line-height: 1.6; font-size: 0.95rem;">
                        Manage your local operations, track live user metrics, and configure system parameters seamlessly.
                    </p>
                </div>
                <div class="text-white-50 small">&copy; 2026 AdminHub</div>
            </div>

            <div class="auth-form-container">
                
                <button type="button" class="panel-toggle-btn" onclick="toggleAuthPanel()" title="Toggle Info Panel">
                    <i class="fa-solid fa-arrows-left-right" id="toggleIcon"></i>
                </button>

                <div class="mb-4">
                    <h2 class="fw-bold mb-1" style="letter-spacing: -0.5px; color: #1e293b;">Sign In</h2>
                    <p class="text-muted" style="font-size: 0.95rem;">Enter your verified credentials to access management panels.</p>
                </div>

                <form method="POST" action="backend/val.php">

                    <?php if (isset($_GET['reason']) && $_GET['reason'] === 'timeout'): ?>
    <div class="alert alert-warning py-2 mb-3 small d-flex align-items-center" style="border-radius: 10px; color: #856404; background-color: #fff3cd; border-color: #ffeeba;">
        <i class="fa-solid fa-triangle-exclamation me-2 fs-6"></i>
        <div>For your security, you were logged out due to inactivity.</div>
    </div>
<?php endif; ?>

<?php if (isset($_GET['status']) && $_GET['status'] === 'loggedout'): ?>
    <div class="alert alert-success py-2 small mb-3" style="border-radius: 10px;">
        <i class="fa-solid fa-circle-check me-2"></i>You have been safely and securely logged out.
    </div>
<?php endif; ?>

                    <?php if (isset($_SESSION['auth_errors'])): ?>
                        <div class="alert alert-danger py-2 mb-3" style="font-size: 0.9rem; border-radius: 10px;">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($_SESSION['auth_errors'] as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['auth_errors']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['auth_success'])): ?>
                        <div class="alert alert-success py-2 small mb-3" style="border-radius: 10px;">
                            <i class="fa-solid fa-circle-check me-2"></i><?php echo $_SESSION['auth_success']; ?>
                        </div>
                        <?php unset($_SESSION['auth_success']); ?>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="username@domain.com" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4" style="font-size: 0.9rem;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                            <label class="form-check-label text-muted" for="rememberMe">Remember this device</label>
                        </div>
                        <a href="#" class="text-link-green">Forgot Password?</a>
                    </div>

                    <button type="submit" name="login_submit" class="btn btn-success btn-submit-action w-100 text-white shadow-sm mb-3">
                        Sign In to Dashboard <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                    
                    <div class="text-center w-100" style="font-size: 0.9rem;">
                        <span class="text-muted">Don't have an account?</span> 
                        <a href="signup.php" class="text-link-green ms-1">Create Account</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        function toggleAuthPanel() {
            const card = document.getElementById('authCard');
            card.classList.toggle('panel-collapsed');
        }
    </script>
</body>
</html>