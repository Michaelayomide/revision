<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recover your AdminHub account password.">
    <title>AdminHub - Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="card forgot-password-card">
        <div class="text-center mb-3">
            <div class="auth-title"><i class="bi bi-shield-lock me-2 text-primary"></i>AdminHub</div>
            <div class="auth-subtitle mt-1">Recover your account credentials</div>
        </div>

        <?php if (isset($_SESSION['auth_errors'])): ?>
            <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                <?php foreach ($_SESSION['auth_errors'] as $error) echo htmlspecialchars($error) . "<br>"; unset($_SESSION['auth_errors']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['auth_success'])): ?>
            <div class="alert alert-success py-2 px-3 mb-3 d-flex align-items-center">
                <i class="bi bi-check-circle me-2"></i> <?php echo $_SESSION['auth_success']; unset($_SESSION['auth_success']); ?>
            </div>
        <?php endif; ?>

        <form action="../backend/val.php" method="POST">
            <div class="mb-4">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>
            </div>

            <button type="submit" name="forgot_submit" class="btn btn-submit-action w-100 mb-3">Send Recovery Link</button>

            <div class="text-center">
                <a href="login.php" class="text-link-brand small"><i class="bi bi-arrow-left me-1"></i> Back to Sign In</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
