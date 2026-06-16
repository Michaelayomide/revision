<?php
session_start();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Forgot Password</title>
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
            color: #ffffff;
            text-align: center;
            margin-bottom: 5px;
        }

        .brand-logo span {
            color: var(--primary-accent);
        }

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
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
            padding: 12px 16px;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-left: none;
            color: #ffffff;
            font-size: 14px;
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--primary-accent);
            box-shadow: none;
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #6b7280;
        }

        .btn-submit {
            background-color: var(--primary-accent);
            border: none;
            color: #ffffff;
            font-weight: 600;
            font-size: 15px;
            padding: 12px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
            color: #ffffff;
        }

        .alert {
            border-radius: 10px;
            border: none;
            font-size: 13px;
        }
        
        .back-to-login {
            color: var(--text-muted);
            font-size: 13px;
            transition: color 0.2s ease;
        }
        
        .back-to-login:hover {
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-logo">Admin<span>Hub</span></div>
    <p class="text-center small mb-4" style="color: var(--text-muted);">Recover your account credentials</p>

    <?php if (isset($_SESSION['auth_errors'])): ?>
        <div class="alert alert-danger py-2.5 px-3">
            <?php foreach ($_SESSION['auth_errors'] as $error) echo htmlspecialchars($error) . "<br>"; unset($_SESSION['auth_errors']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['auth_success'])): ?>
        <div class="alert alert-success py-2.5 px-3">
            <?php echo $_SESSION['auth_success']; unset($_SESSION['auth_success']); ?>
        </div>
    <?php endif; ?>

    <form action="../backend/val.php" method="POST">
        <div class="mb-4">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
            </div>
        </div>

        <button type="submit" name="forgot_submit" class="btn btn-submit w-100 mb-3">Send Recovery Link</button>
        
        <div class="text-center">
            <a href="login.php" class="back-to-login text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Back to Sign In</a>
        </div>
    </form>
</div>

</body>
</html>