<?php
session_start();

// 1. Establish database connection link
require_once '../backend/db.php';
$database = $db ?? $pdo; // Dynamically grab your active connection driver

// 2. Fetch token from URL parameter query (?token=...)
$token = $_GET['token'] ?? '';
$isValid = false;

if (!empty($token)) {
    try {
        // Look for matching token that hasn't expired yet (NOW() compares against database time)
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
    <title>AdminHub - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0c111d;
            --card-bg: #161e2e;
            --input-bg: #1f2a3c;
            --text-muted: #9ca3af;
            --primary-accent: #3b82f6;
            --primary-hover: #2563eb;
            --success-accent: #10b981;
            --success-hover: #059669;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-card {
            background-color: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            width: 100%;
            max-width: 440px;
            padding: 40px 35px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .brand-logo {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 5px;
        }

        .brand-logo span { color: var(--primary-accent); }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #e5e7eb;
            margin-bottom: 8px;
        }

        .input-group-text {
            background-color: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-right: none;
            color: var(--text-muted);
            border-radius: 10px 0 0 10px;
            padding: 12px 16px;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-left: none;
            color: #ffffff;
            font-size: 14px;
            border-radius: 0 10px 10px 0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--primary-accent);
            box-shadow: none;
            color: #ffffff;
        }

        .btn-submit {
            background-color: var(--success-accent);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            padding: 12px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .btn-submit:hover { background-color: var(--success-hover); }
        .alert { border-radius: 10px; border: none; font-size: 13px; }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-logo">Admin<span>Hub</span></div>
    <p class="text-center small mb-4" style="color: var(--text-muted);">Create your new security credentials</p>

    <?php if (isset($_SESSION['auth_errors'])): ?>
        <div class="alert alert-danger py-2.5 px-3 mb-3">
            <?php foreach ($_SESSION['auth_errors'] as $error) echo htmlspecialchars($error) . "<br>"; unset($_SESSION['auth_errors']); ?>
        </div>
    <?php endif; ?>

    <?php if ($isValid): ?>
        <form action="../backend/val.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 8 characters" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
                </div>
            </div>

            <button type="submit" name="reset_submit" class="btn btn-submit w-100">Save & Apply Changes</button>
        </form>
    <?php else: ?>
        <div class="alert alert-danger text-center py-4 mb-4" style="background-color: rgba(239, 68, 68, 0.1); color: #fca5a5;">
            <i class="bi bi-exclamation-octagon fs-2 d-block mb-2 text-danger"></i>
            This token validation session has expired or is broken.
        </div>
        <div class="text-center">
            <a href="forgot-password.php" class="btn btn-sm btn-outline-light rounded-pill px-4 py-2" style="font-size: 12px;">Request New Token Link</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>