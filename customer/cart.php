<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$customerId = (int)$_SESSION['customer_id'];

$stmt = $database->prepare(
    "SELECT ci.id AS cart_item_id, ci.quantity, p.id AS product_id, p.name, p.price, p.stock_quantity 
     FROM cart_items ci 
     JOIN products p ON ci.product_id = p.id 
     WHERE ci.customer_id = :cid"
);
$stmt->execute(['cid' => $customerId]);
$cartItems = $stmt->fetchAll();

$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += (float)$item['price'] * (int)$item['quantity'];
}

$shipping = $subtotal > 0 ? 15.00 : 0.0; // Flat base template rule
$total = $subtotal + $shipping;
$flashMessages = consume_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <h1 class="fw-bold mb-4">Your Shopping Cart</h1>
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?php echo e($msg['type']); ?>"><?php echo e($msg['message']); ?></div>
    <?php endforeach; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-3 mb-4">
                <?php if (!empty($cartItems)): ?>
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($item['name']); ?></td>
                                    <td><?php echo money($item['price']); ?></td>
                                    <td>
                                        <form method="POST" action="customer_action.php" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="cart_update">
                                            <input type="hidden" name="cart_item_id" value="<?php echo e($item['cart_item_id']); ?>">
                                            <input type="number" name="quantity" class="form-control form-control-sm" style="width: 70px;" value="<?php echo e($item['quantity']); ?>" min="1" max="<?php echo e($item['stock_quantity']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Update</button>
                                        </form>
                                    </td>
                                    <td class="fw-bold"><?php echo money((float)$item['price'] * (int)$item['quantity']); ?></td>
                                    <td>
                                        <form method="POST" action="customer_action.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="cart_remove">
                                            <input type="hidden" name="cart_item_id" value="<?php echo e($item['cart_item_id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted text-center py-4 my-0">Your shopping cart is empty. <a href="shop.php">Go shopping</a></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold mb-3">Order Summary</h4>
                <div class="d-flex justify-content-between mb-2"><span>Subtotal:</span><strong><?php echo money($subtotal); ?></strong></div>
                <div class="d-flex justify-content-between mb-2"><span>Shipping Fee:</span><strong><?php echo money($shipping); ?></strong></div>
                <hr>
                <div class="d-flex justify-content-between mb-4"><span class="fs-5 fw-bold">Total:</span><strong class="fs-5 text-success"><?php echo money($total); ?></strong></div>
                <a href="checkout.php" class="btn btn-primary w-100 <?php echo empty($cartItems) ? 'disabled' : ''; ?>">Proceed to Checkout</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>