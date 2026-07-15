<?php 
session_start(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a new AdminHub administrator account.">
    <title>AdminHub - Create Admin Account</title>
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
                    <h2 class="auth-hero-title">Join the Platform</h2>
                    <p class="auth-hero-text">
                        Create a secure administrator account to oversee system parameters
                        and track operational records.
                    </p>
                    <div class="auth-feature"><i class="fa-solid fa-key"></i> Encrypted credential storage</div>
                    <div class="auth-feature"><i class="fa-solid fa-sliders"></i> Granular role permissions</div>
                    <div class="auth-feature"><i class="fa-solid fa-rotate"></i> Self-service recovery</div>
                </div>
                <div class="auth-copyright">&copy; 2026 AdminHub. All rights reserved.</div>
            </div>

            <div class="auth-form-container">
                <div class="auth-title">Create Account</div>
                <div class="auth-subtitle">Set up your administrator credentials below.</div>

                <form action="../backend/val.php" method="POST" novalidate>

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

                    <div class="mb-3">
                        <label for="fullname" class="form-label">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-user"></i></span>
                            <input type="text" class="form-control" id="fullname" name="fullname" placeholder="John Doe" required autocomplete="name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="username@domain.com" required autocomplete="email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••••••" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="••••••••••••" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-check mb-4" style="font-size: var(--fs-body);">
                        <input class="form-check-input" type="checkbox" id="termsCheck" name="terms_agreement" required>
                        <label class="form-check-label text-muted" for="termsCheck">
                            I agree to the platform <a href="#" class="text-link-brand">Terms &amp; Privacy Rules</a>.
                        </label>
                    </div>

                    <button type="submit" name="signup_submit" class="btn btn-submit-action w-100 mb-3">
                        Register Admin Workspace <i class="fa-solid fa-user-plus ms-1"></i>
                    </button>

                    <div class="text-center w-100" style="font-size: var(--fs-body);">
                        <span class="text-muted">Already have an account?</span>
                        <a href="login.php" class="text-link-brand ms-1">Sign In</a>
                    </div>
                </form>
            </div>

        </div>
    </div>

</body>
</html>
