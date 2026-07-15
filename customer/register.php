<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header('Location: shop.php');
    exit();
}

$errors = [];

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
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

    <main class="auth-card-box auth-wide" role="main">
        <div class="auth-brand mb-3">
            <span class="auth-logo-mark"><i class="bi bi-bag-heart-fill"></i></span>
            <?php echo e(app_setting('website_name', 'AdminHub')); ?>
        </div>
        <h1 class="auth-title">Create Customer Account</h1>
        <p class="auth-subtitle">Fill in your details to get started.</p>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger py-2"><?php echo e($err); ?></div>
        <?php endforeach; ?>

        <form method="POST" action="register.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label" for="fullname">Full Name *</label>
                    <input type="text" name="fullname" id="fullname" class="form-control" autocomplete="name" required value="<?php echo e($fullname ?? ''); ?>">
                </div>

                <div class="col-lg-6">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control" autocomplete="email" required value="<?php echo e($email ?? ''); ?>">
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="phone_number">Phone Number *</label>
                    <input type="tel" name="phone_number" id="phone_number" class="form-control" autocomplete="tel" inputmode="tel" required value="<?php echo e($phone ?? ''); ?>">
                </div>

                <div class="col-lg-6">
                    <label class="form-label" for="password">Password *</label>
                    <div class="pw-field">
                        <input type="password" name="password" id="password" class="form-control" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <div class="pw-field">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" autocomplete="new-password" required>
                        <button type="button" class="pw-toggle" data-target="confirm_password" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 section-gap">
                    <label class="form-label" for="residential_address">Residential Address *</label>
                    <input type="text" name="residential_address" id="residential_address" class="form-control" autocomplete="street-address" required value="<?php echo e($address ?? ''); ?>">
                </div>

                <div class="col-lg-6">
                    <label class="form-label" for="city">City *</label>
                    <input type="text" name="city" id="city" class="form-control" autocomplete="address-level2" required value="<?php echo e($city ?? ''); ?>">
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="state_province">State / Province *</label>
                    <input type="text" name="state_province" id="state_province" class="form-control" autocomplete="address-level1" required value="<?php echo e($state ?? ''); ?>">
                </div>

                <div class="col-lg-6">
                    <label class="form-label" for="country">Country *</label>
                    <input type="text" name="country" id="country" class="form-control" autocomplete="country-name" required value="<?php echo e($country ?? ''); ?>">
                </div>
                <div class="col-lg-6">
                    <label class="form-label" for="postal_code">Postal / ZIP Code</label>
                    <input type="text" name="postal_code" id="postal_code" class="form-control" autocomplete="postal-code" inputmode="numeric" value="<?php echo e($postal ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn-brand section-gap">Register Account</button>
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
