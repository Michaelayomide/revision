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
    <title>Customer Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="fw-bold text-center mb-4">Create Customer Account</h2>
                    
                    <?php foreach ($errors as $err): ?>
                        <div class="alert alert-danger"><?php echo e($err); ?></div>
                    <?php endforeach; ?>

                    <form method="POST" action="register.php">
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        
                        <div class="row g-3">
                            <div class="col-100">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="fullname" class="form-control" required value="<?php echo e($fullname ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required value="<?php echo e($email ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="text" name="phone_number" class="form-control" required value="<?php echo e($phone ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm Password *</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <div class="col-100">
                                <label class="form-label">Residential Address *</label>
                                <input type="text" name="residential_address" class="form-control" required value="<?php echo e($address ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">City *</label>
                                <input type="text" name="city" class="form-control" required value="<?php echo e($city ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">State / Province *</label>
                                <input type="text" name="state_province" class="form-control" required value="<?php echo e($state ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Country *</label>
                                <input type="text" name="country" class="form-control" required value="<?php echo e($country ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal / ZIP Code</label>
                                <input type="text" name="postal_code" class="form-control" value="<?php echo e($postal ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-4">Register Account</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">Already have an account? Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>