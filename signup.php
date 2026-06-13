<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Create Admin Account</title>
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
                    <h2 class="fw-bold mb-3" style="letter-spacing: -0.5px;">Join the Platform</h2>
                    <p class="text-white-50 m-0" style="line-height: 1.6; font-size: 0.95rem;">
                        Create a secure administrator account to begin overseeing system parameters and tracking operational records.
                    </p>
                </div>
                <div class="text-white-50 small">&copy; 2026 AdminHub</div>
            </div>

            <div class="auth-form-container">
                
                <button type="button" class="panel-toggle-btn" onclick="toggleAuthPanel()" title="Toggle Info Panel">
                    <i class="fa-solid fa-arrows-left-right"></i>
                </button>

                <div class="mb-4">
                    <h2 class="fw-bold mb-1" style="letter-spacing: -0.5px; color: #1e293b;">Create Account</h2>
                    <p class="text-muted" style="font-size: 0.9rem;">Set up your specialized dashboard credential entry parameters below.</p>
                </div>

              <form method="POST" action="backend/val.php">
    
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

    <div class="mb-3">
        <label for="fullname" class="form-label small fw-semibold text-secondary">Full Name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="John Doe" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="username@domain.com" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required>
        </div>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label small fw-semibold text-secondary">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required>
        </div>
    </div>

    <div class="form-check mb-4" style="font-size: 0.9rem;">
        <input class="form-check-input" type="checkbox" id="termsCheck" name="terms_agreement" required>
        <label class="form-check-label text-muted" for="termsCheck">
            I agree to the platform <a href="#" class="text-link-green">Terms & Privacy Rules</a>.
        </label>
    </div>

    <button type="submit" name="signup_submit" class="btn btn-success btn-submit-action w-100 text-white shadow-sm mb-3">
        Register Admin Workspace <i class="fa-solid fa-user-plus ms-2"></i>
    </button>

    <div class="text-center w-100" style="font-size: 0.9rem;">
                        <span class="text-muted">DO You have An accounyt?</span> 
                        <a href="login.php" class="text-link-green ms-1">Login Account</a>
                    </div>
</form>
                    
            </div>

        </div>
    </div>

    <script>
        function toggleAuthPanel() {
            document.getElementById('authCard').classList.toggle('panel-collapsed');
        }
    </script>
</body>
</html>