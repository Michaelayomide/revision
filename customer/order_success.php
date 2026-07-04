<?php
// customer/order_success.php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$orderId = $_SESSION['last_order_id'] ?? null;

// If a user navigates manually without an active purchase history context, route back
if (!$orderId) {
    header('Location: shop.php');
    exit();
}

// Clear the single-use session tracker value safely
unset($_SESSION['last_order_id']);

// Pull order summary metrics out of the system records for confirmation layout rendering
$stmt = $database->prepare("SELECT * FROM orders WHERE id = :id AND customer_id = :cid LIMIT 1");
$stmt->execute(['id' => $orderId, 'cid' => $_SESSION['customer_id']]);
$order = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed!</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="card border-0 shadow-sm p-5 rounded-4">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="fw-bold text-dark mb-2">Thank You!</h1>
                <p class="text-muted fs-5">Your order has been successfully processed.</p>
                
                <?php if ($order): ?>
                    <div class="bg-light rounded-3 p-3 my-4 border text-start">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Order Reference ID:</span>
                            <span class="fw-bold text-dark">#<?= htmlspecialchars((string)$order['id']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Payment Mode:</span>
                            <span class="badge bg-secondary"><?= htmlspecialchars((string)$order['payment_method']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-0">
                            <span class="text-secondary">Total Settled Amount:</span>
                            <strong class="text-success"><?= money((float)$order['total_amount']) ?></strong>
                        </div>
                    </div>
                <?php endif; ?>
                
                <p class="small text-muted mb-4">A digital system copy tracking payload configuration statement details has been attached to your buyer profile panel tracking matrix.</p>
                
                <div class="d-grid gap-2">
                    <a href="shop.php" class="btn btn-primary btn-lg fw-semibold">Continue Shopping</a>
                    <a href="orders.php" class="btn btn-outline-secondary">Track My Orders</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>