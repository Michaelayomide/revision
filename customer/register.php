<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header('Location: shop.php');
    exit();
}

$errors = [];

$fullname = '';
$email = '';
$phone = '';
$address = '';
$city = '';
$state = '';
$country = '';
$postal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token invalid.';
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['residential_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state_province'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $postal = trim($_POST['postal_code'] ?? '');

    // Strict Validation Checks
    if ($fullname === '' || $email === '' || $phone === '' || $password === '' || $address === '' || $city === '' || $state === '' || $country === '') {
        $errors[] = 'All required fields must be completed.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email format.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if (strlen($address) < 5 || strlen($address) > 255) {
        $errors[] = 'Residential address must be between 5 and 255 characters.';
    }
    if (!preg_match("/^[A-Za-zÀ-ÿ .'-]{2,100}$/u", $city)) {
        $errors[] = 'Please enter a valid city.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors[] = 'Please provide a valid email format.';
    }
    if (!preg_match("/^[A-Za-zÀ-ÿ .'-]{2,100}$/u", $country)) {
        $errors[] = 'Please enter a valid country.';
    }

    if (empty($errors)) {
        try {
            // Uniqueness Check
            $stmt = $database->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email address is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insert = $database->prepare(
                    "INSERT INTO customers (fullname, email, phone_number, password_hash, residential_address, city, state_province, country, postal_code, is_active)
                     VALUES (:name, :email, :phone, :pass, :addr, :city, :state, :country, :postal, 1)"
                );
                $insert->execute([
                    'name' => $fullname,
                    'email' => $email,
                    'phone' => $phone,
                    'pass' => $hashedPassword,
                    'addr' => $address,
                    'city' => $city,
                    'state' => $state,
                    'country' => $country,
                    'postal' => $postal !== '' ? $postal : null
                ]);

                flash('success', 'Registration successful! Please log in.');
                header('Location: login.php');
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = 'Database registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your <?php echo e(app_setting('website_name', 'AdminHub')); ?> customer account.">
    <title>Customer Registration &middot; <?php echo e(app_setting('website_name', 'AdminHub')); ?></title>
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
            padding: 1.5rem 1rem;
        }

        /* Ambient Glowing Background Elements */
        .ambient-glow {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 1;
            filter: blur(100px);
            opacity: 0.12;
        }
        .glow-1 {
            background: #3b82f6;
            top: -10%;
            left: -10%;
        }
        .glow-2 {
            background: #8b5cf6;
            bottom: -10%;
            right: -10%;
        }

        /* Glassmorphism Dynamic Responsive Card */
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
        .auth-card-box.auth-wide {
            max-width: 720px;
        }

        @media (max-width: 768px) {
            body.auth-page {
                padding: 0.75rem;
            }
            .auth-card-box {
                padding: 2rem 1.25rem;
                border-radius: 16px;
            }
            .ambient-glow {
                width: 250px;
                height: 250px;
                filter: blur(70px);
            }
        }

        /* Header Layout Styling */
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

        /* Form Layout & Field Grid */
        .field {
            margin-bottom: 0rem; /* Handled gracefully by row's 'g-3' */
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
            height: 48px; /* Optimized mobile tap height target */
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            color: #ffffff;
            padding: 0 1rem 0 3rem;
            font-size: 0.95rem; /* Prevents auto-zoom on iOS */
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

        /* Primary Button */
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
            margin-top: 1rem;
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

        /* Footer Links */
        .auth-foot {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.2s;
            margin-top: 2rem;
            display: block;
            font-size: 0.9rem;
        }
        .auth-foot a {
            color: #3b82f6;
            font-weight: 600;
        }
        .auth-foot a:hover {
            color: #ffffff;
            text-decoration: underline;
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

    <main class="auth-card-box auth-wide" role="main">
        <!-- Responsive Icon -->
        <div class="cart-logo mb-3">
            <svg width="64" height="64" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M15 25H25L35 62H75L85 35H30" stroke="#3b82f6" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="40" cy="74" r="5" stroke="#3b82f6" stroke-width="3.5" fill="none"/>
                <circle cx="70" cy="74" r="5" stroke="#3b82f6" stroke-width="3.5" fill="none"/>
                <path d="M55 46V30M55 30L49 36M55 30L61 36" stroke="#ffffff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="auth-title-container">
            <h1>Create Account</h1>
            <p class="auth-subtitle">Join us and start managing your orders today.</p>
        </div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
        <?php endforeach; ?>

        <form method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <div class="row g-3">
                <!-- Full Name -->
                <div class="col-12 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-person"></i></span>
                        <input type="text" name="fullname" id="fullname" class="form-control" placeholder="Full Name *" autocomplete="name" required value="<?php echo e($fullname); ?>">
                    </div>
                </div>

                <!-- Email Address -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email Address *" autocomplete="email" required value="<?php echo e($email); ?>">
                    </div>
                </div>

                <!-- Phone Number -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-telephone"></i></span>
                        <input type="tel" name="phone_number" id="phone_number" class="form-control" placeholder="Phone Number *" autocomplete="tel" inputmode="tel" required value="<?php echo e($phone); ?>">
                    </div>
                </div>

                <!-- Password -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password *" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-shield-check"></i></span>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password *" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Residential Address -->
                <div class="col-12 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-geo-alt"></i></span>
                        <input type="text" name="residential_address" id="residential_address" class="form-control" placeholder="Residential Address *" autocomplete="street-address" required value="<?php echo e($address); ?>">
                    </div>
                </div>

                <!-- City -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-building"></i></span>
                        <input type="text" name="city" id="city" class="form-control" placeholder="City *" autocomplete="address-level2" required value="<?php echo e($city); ?>">
                    </div>
                </div>

                <!-- State / Province -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-map"></i></span>
                        <input type="text" name="state_province" id="state_province" class="form-control" placeholder="State / Province *" autocomplete="address-level1" required value="<?php echo e($state); ?>">
                    </div>
                </div>

                <!-- Country -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-globe"></i></span>
                        <input type="text" name="country" id="country" class="form-control" placeholder="Country *" autocomplete="country-name" required value="<?php echo e($country); ?>">
                    </div>
                </div>

                <!-- Postal / ZIP Code -->
                <div class="col-md-6 field">
                    <div class="input-group-custom">
                        <span class="input-icon"><i class="bi bi-hash"></i></span>
                        <input type="text" name="postal_code" id="postal_code" class="form-control" placeholder="Postal / ZIP Code" autocomplete="postal-code" inputmode="numeric" value="<?php echo e($postal); ?>">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-brand mt-4">Register Account</button>
        </form>

        <div class="auth-foot">
            Already have an account? <a href="login.php">Login here</a>
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