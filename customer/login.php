<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header('Location: shop.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $database->prepare("SELECT * FROM customers WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $customer = $stmt->fetch();

        if ($customer && (int)$customer['is_active'] === 1 && password_verify($password, $customer['password_hash'])) {
            $_SESSION['customer_logged_in'] = true;
            $_SESSION['customer_id'] = (int)$customer['id'];
            $_SESSION['customer_name'] = $customer['fullname'];

            header('Location: shop.php');
            exit();
        } else {
            $error = 'Invalid email, password, or account is deactivated.';
        }
    } else {
        $error = 'Please fill out all fields.';
    }
}
$flashMessages = consume_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to your <?php echo e(app_setting('website_name', 'AdminHub')); ?> customer account.">
    <title>Customer Login &middot; <?php echo e(app_setting('website_name', 'AdminHub')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body.auth-page {
            background-color: #0b0f19;
            background-image: radial-gradient(circle at 50% 50%, #1a2c5b 0%, #070a13 100%);
            color: #f3f4f6;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            position: relative;
            padding: 1rem;
        }

        /* Ambient Glowing Background Elements */
        .ambient-glow {
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            filter: blur(80px);
            opacity: 0.15;
        }
        .glow-1 {
            background: #3b82f6;
            top: -5%;
            left: -5%;
        }
        .glow-2 {
            background: #8b5cf6;
            bottom: -5%;
            right: -5%;
        }

        /* Glassmorphism Card Container */
        .auth-card-box {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 440px;
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 2.5rem 1.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            box-sizing: border-box;
        }

        @media (max-width: 576px) {
            body.auth-page {
                padding: 0.75rem;
            }
            .auth-card-box {
                padding: 2rem 1.25rem;
                border-radius: 16px;
            }
            .ambient-glow {
                width: 200px;
                height: 200px;
                filter: blur(60px);
            }
        }

        /* Header Accent Title */
        .auth-title-container h1 {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        .auth-subtitle {
            color: #9ca3af;
            font-size: 0.88rem;
            margin-bottom: 2rem;
        }

        /* SVG Cart Animation & Styling */
        .cart-logo {
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .cart-logo:hover {
            transform: scale(1.05) rotate(-3deg);
        }

        /* Form Controls & Styling */
        .field {
            margin-bottom: 1.15rem;
            position: relative;
            width: 100%;
        }
        .input-group-custom {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        .input-group-custom .form-control {
            width: 100%;
            height: 48px; /* Standard optimized mobile touch height */
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: #ffffff;
            padding: 0 1rem 0 3rem;
            font-size: 0.95rem; /* Prevents iOS auto-zoom on focus */
            font-weight: 400;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-sizing: border-box;
        }
        .input-group-custom .form-control::placeholder {
            color: #6b7280;
        }
        .input-group-custom .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
            color: #ffffff;
            outline: none;
        }
        .input-group-custom .input-icon {
            position: absolute;
            left: 1.15rem;
            color: #9ca3af;
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            pointer-events: none;
            transition: color 0.2s ease;
        }
        .input-group-custom .form-control:focus ~ .input-icon {
            color: #3b82f6;
        }

        /* Password Show/Hide Toggle */
        .pw-toggle {
            position: absolute;
            right: 0.5rem;
            background: none;
            border: none;
            color: #6b7280;
            padding: 0 0.75rem;
            cursor: pointer;
            z-index: 10;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }
        .pw-toggle:hover {
            color: #ffffff;
        }

        /* Button Design */
        .btn-brand {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            height: 48px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            margin-top: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-brand:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            filter: brightness(1.1);
        }
        .btn-brand:active {
            transform: translateY(1px);
        }

        /* Checkbox & Links */
        .auth-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.85rem;
        }
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            width: 18px;
            height: 18px;
        }
        .form-check-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
        }
        .auth-foot, .auth-row a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
        }
        .auth-foot a {
            color: #3b82f6;
            font-weight: 600;
        }
        .auth-foot a:hover, .auth-row a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
        .auth-foot {
            margin-top: 2rem;
            display: block;
            font-size: 0.9rem;
        }

        /* Alerts Styling */
        .alert {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #ffffff;
            backdrop-filter: blur(10px);
            font-size: 0.85rem;
            border-radius: 12px;
            text-align: left;
            margin-bottom: 1.5rem;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
    </style>
</head>
<body class="auth-page">

    <div class="ambient-glow glow-1"></div>
    <div class="ambient-glow glow-2"></div>

    <main class="auth-card-box" role="main">
        <!-- Animated Responsive SVG Cart -->
        <div class="cart-logo mb-3">
            <svg width="64" height="64" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 25H25L35 62H75L85 35H30" stroke="#3b82f6" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="40" cy="74" r="5" stroke="#3b82f6" stroke-width="3.5" fill="none"/>
                <circle cx="70" cy="74" r="5" stroke="#3b82f6" stroke-width="3.5" fill="none"/>
                <path d="M55 46V30M55 30L49 36M55 30L61 36" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="auth-title-container">
            <h1>Welcome Back</h1>
            <p class="auth-subtitle">Sign in to continue to your account.</p>
        </div>

        <?php foreach ($flashMessages as $msg): ?>
            <div class="alert alert-<?php echo e($msg['type']); ?> py-2"><?php echo e($msg['message']); ?></div>
        <?php endforeach; ?>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <!-- Email Input -->
            <div class="field">
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-person"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" required autocomplete="username">
                </div>
            </div>

            <!-- Password Input -->
            <div class="field">
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required autocomplete="current-password">
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="auth-row">
                <div class="form-check mb-0 d-flex align-items-center">
                    <input class="form-check-input me-2" type="checkbox" id="rememberMe" name="remember_me">
                    <label class="form-check-label text-white-50" for="rememberMe">Remember</label>
                </div>
                <a href="forgot-password.php">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-brand">Sign In</button>
        </form>

        <div class="auth-foot">
            Don't have an account? <a href="register.php">Create Account</a>
        </div>
    </main>

    <script>
        document.querySelectorAll('.pw-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.target);
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });
    </script>

</body>
</html>