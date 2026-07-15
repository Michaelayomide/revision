<?php
session_start();

require_once '../backend/db.php';
$database = $db ?? $pdo;

$token = $_GET['token'] ?? '';
$isValid = false;

if (!empty($token)) {
    try {
        $stmt = $database->prepare("SELECT id FROM admins WHERE reset_token = :token AND token_expire > NOW() LIMIT 1");
        $stmt->execute(['token' => $token]);
        if ($stmt->fetch()) {
            $isValid = true;
        }
    } catch (PDOException $e) {
        $isValid = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your AdminHub password.">
    <title>AdminHub - Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-wrapper">
    <div class="card forgot-password-card">
        <div class="text-center mb-3">
            <div class="auth-title"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>AdminHub</div>
            <div class="auth-subtitle mt-1">Create your new security credentials</div>
        </div>

        <?php if (isset($_SESSION['auth_errors'])): ?>
            <div class="alert alert-danger py-2 px-3 mb-3" role="alert">
                <?php foreach ($_SESSION['auth_errors'] as $error) echo htmlspecialchars($error) . "<br>"; unset($_SESSION['auth_errors']); ?>
            </div>
        <?php endif; ?>

        <?php if ($isValid): ?>
            <form action="../backend/val.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-3">
                    <label class="form-label" for="password">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters" required autocomplete="new-password">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat new password" required autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" name="reset_submit" class="btn btn-submit-action w-100">Save &amp; Apply Changes</button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger text-center py-3 mb-3" role="alert">
                <i class="bi bi-exclamation-octagon fs-4 d-block mb-2 text-danger"></i>
                This token validation session has expired or is broken.
            </div>
            <div class="text-center">
                <a href="forgot-password.php" class="btn btn-sm btn-outline-danger px-4 py-2">Request New Token Link</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
