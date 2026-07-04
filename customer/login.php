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
    <title>Customer Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?php foreach ($flashMessages as $msg): ?>
                <div class="alert alert-<?php echo e($msg['type']); ?>"><?php echo e($msg['message']); ?></div>
            <?php endforeach; ?>
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="fw-bold text-center mb-4">Customer Login</h3>
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Sign In</button>
                    </form>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none">New customer? Create an account</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>