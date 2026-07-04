<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$customerId = (int)$_SESSION['customer_id'];

// Get Customer Profile details
$custStmt = $database->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$custStmt->execute(['id' => $customerId]);
$customer = $custStmt->fetch();

// Look up current validation items
$stmt = $database->prepare(
    "SELECT ci.quantity, p.name, p.price FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.customer_id = :cid"
);
$stmt->execute(['cid' => $customerId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    header('Location: cart.php');
    exit();
}

$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}
$shipping = 15.00;
$total = $subtotal + $shipping;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="fw-bold mb-4">Secure Checkout</h1>
    <div class="row">
        <div class="col-md-7">
            <div class="card border-0 shadow-sm p-4 mb-4">
                <h4 class="fw-bold mb-3">Shipping & Fulfillment Details</h4>
                <form method="POST" action="customer_action.php">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="place_order">

                    <div class="mb-3">
                        <label class="form-label">Recipient Name</label>
                        <input type="text" class="form-control" value="<?php echo e($customer['fullname']); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Phone (Confirm/Update)</label>
                        <input type="text" name="shipping_phone" class="form-control" value="<?php echo e($customer['phone_number']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shipping Address Destination</label>
                        <textarea name="shipping_address" class="form-control" rows="3" required><?php echo e($customer['residential_address'] . ", " . $customer['city'] . ", " . $customer['state_province'] . ", " . $customer['country']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Option</label>
                        <select name="payment_method" class="form-select">
                            <option value="Cash on Delivery">Cash on Delivery</option>
                            <option value="Bank Transfer">Direct Bank Transfer</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 mt-3">Confirm & Place Order</button>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-3">Review Items</h4>
                <ul class="list-group list-group-flush mb-3">
                    <?php foreach ($cartItems as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <span class="fw-semibold"><?php echo e($item['name']); ?></span>
                                <small class="text-muted d-block">Qty: <?php echo e($item['quantity']); ?></small>
                            </div>
                            <span class="text-muted"><?php echo money((float)$item['price'] * (int)$item['quantity']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><span><?php echo money($subtotal); ?></span></div>
                <div class="d-flex justify-content-between mb-2"><span>Shipping:</span><span><?php echo money($shipping); ?></span></div>
                <hr>
                <div class="d-flex justify-content-between mb-0"><span class="fw-bold">Total Amount:</span><strong class="text-success fs-5"><?php echo money($total); ?></strong></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>