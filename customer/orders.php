<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$customerId = (int)$_SESSION['customer_id'];

// Fetch order history for the logged-in customer only
$stmt = $database->prepare(
    "SELECT * FROM orders WHERE customer_id = :cid ORDER BY created_at DESC"
);
$stmt->execute(['cid' => $customerId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold">Your Order History</h1>
        <a href="shop.php" class="btn btn-outline-primary">Back to Shop</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total Spent</th>
                            <th>Order Status</th>
                            <th>Fulfillment Status</th>
                            <th>Tracking Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $ord): ?>
                                <?php
                                    $statusColor = match($ord['order_status']) {
                                        'Pending' => 'bg-warning text-dark',
                                        'Approved' => 'bg-info text-white',
                                        'Shipped' => 'bg-primary text-white',
                                        'Delivered' => 'bg-success text-white',
                                        'Cancelled' => 'bg-danger text-white',
                                        default => 'bg-secondary text-white'
                                    };
                                ?>
                                <tr>
                                    <td class="fw-bold text-secondary"><?php echo e($ord['txn_id']); ?></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y H:i', strtotime($ord['created_at']))); ?></td>
                                    <td class="fw-semibold text-success"><?php echo money($ord['total_amount']); ?></td>
                                    <td><span class="badge <?php echo $statusColor; ?>"><?php echo e($ord['order_status']); ?></span></td>
                                    <td class="text-muted small"><?php echo e($ord['shipping_status']); ?></td>
                                    <td>
                                        <?php if (!empty($ord['tracking_link'])): ?>
                                            <a href="<?php echo urlencode($ord['tracking_link']); ?>" target="_blank" class="btn btn-sm btn-link p-0 text-decoration-none">Track Package</a>
                                        <?php else: ?>
                                            <span class="text-muted small">Not Available Yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">You have not placed any orders yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>