<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle = 'Order Confirmed';

$orderId = $_SESSION['last_order_id'] ?? null;
if (!$orderId) {
    redirect_to('shop.php');
}

// Consume the session value — prevents re-viewing on refresh (but we re-query by id safely)
unset($_SESSION['last_order_id']);

// Fetch order details
$stmt = $database->prepare(
    "SELECT o.*, c.fullname AS customer_fullname
     FROM orders o
     LEFT JOIN customers c ON o.customer_id = c.id
     WHERE o.id = :id AND o.customer_id = :cid
     LIMIT 1"
);
$stmt->execute(['id' => $orderId, 'cid' => (int) $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect_to('orders.php');
}

// Fetch order items
$itemStmt = $database->prepare(
    "SELECT oi.*, p.image_path
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = :oid"
);
$itemStmt->execute(['oid' => $orderId]);
$orderItems = $itemStmt->fetchAll();

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<style>
.success-icon-wrap {
    width: 96px;
    height: 96px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 0 0 12px rgba(16,185,129,0.12), 0 0 0 24px rgba(16,185,129,0.06);
    animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes popIn {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
.confetti-dot {
    width: 8px; height: 8px; border-radius: 50%;
    display: inline-block; margin: 0 3px;
    animation: bounce 0.8s infinite alternate;
}
@keyframes bounce { from { transform: translateY(0); } to { transform: translateY(-10px); } }
</style>

<div class="customer-main">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <!-- Success Card -->
            <div class="stat-card text-center mb-4 py-5">
                <!-- Animated Checkmark -->
                <div class="success-icon-wrap">
                    <i class="bi bi-check-lg text-white" style="font-size:3rem;"></i>
                </div>

                <div class="mb-2" style="font-size:1.3rem;">
                    <span class="confetti-dot" style="background:#10b981; animation-delay:0s;"></span>
                    <span class="confetti-dot" style="background:#3b82f6; animation-delay:0.15s;"></span>
                    <span class="confetti-dot" style="background:#f59e0b; animation-delay:0.3s;"></span>
                    <span class="confetti-dot" style="background:#ef4444; animation-delay:0.45s;"></span>
                </div>

                <h1 class="fw-bold text-dark mb-2" style="font-size:1.8rem;">Order Successfully Placed!</h1>
                <p class="text-muted fs-6 mb-0">
                    Thank you for your purchase. Your order has been received and is being processed.
                </p>
            </div>

            <!-- Order Details -->
            <div class="stat-card mb-4">
                <h5 class="fw-bold mb-4">
                    <i class="bi bi-receipt me-2" style="color:var(--brand-primary);"></i>
                    Order Details
                </h5>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Order Number</div>
                        <div class="fw-bold fs-6" style="color:var(--brand-primary);">
                            <?= e($order['txn_id']) ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Order Date</div>
                        <div class="fw-semibold">
                            <?= date('M j, Y — g:i A', strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Payment Method</div>
                        <div class="fw-semibold">
                            <span class="badge rounded-pill" style="background:rgba(16,185,129,0.12); color:var(--brand-primary);">
                                <?= e($order['payment_method']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:0.78rem; text-transform:uppercase; letter-spacing:0.6px; font-weight:600;">Order Status</div>
                        <div>
                            <span class="status-badge status-pending">
                                <i class="bi bi-clock"></i> <?= e($order['order_status']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($order['shipping_address'])): ?>
                    <div class="p-3 rounded-3 mb-3" style="background:var(--surface-2); border:1px solid var(--border);">
                        <div class="text-muted mb-1" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.6px;">
                            <i class="bi bi-geo-alt me-1"></i> Shipping To
                        </div>
                        <div style="font-size:0.88rem;"><?= e($order['shipping_address']) ?></div>
                        <?php if (!empty($order['shipping_phone'])): ?>
                            <div class="text-muted mt-1" style="font-size:0.82rem;">
                                <i class="bi bi-telephone me-1"></i><?= e($order['shipping_phone']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Total -->
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3"
                     style="background:linear-gradient(135deg, rgba(16,185,129,0.08), rgba(16,185,129,0.03)); border:1px solid rgba(16,185,129,0.2);">
                    <span class="fw-bold fs-5">Total Amount</span>
                    <span class="fw-bold fs-4 text-success"><?= money((float) $order['total_amount']) ?></span>
                </div>
            </div>

            <!-- Items Ordered -->
            <?php if (!empty($orderItems)): ?>
                <div class="stat-card mb-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-box-seam me-2" style="color:var(--brand-primary);"></i>
                        Items Ordered
                    </h5>
                    <?php foreach ($orderItems as $item): ?>
                        <?php
                            $imgSrc = (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . $item['image_path']))
                                         ? '../' . e($item['image_path'])
                                         : null;
                        ?>
                        <div class="d-flex align-items-center gap-3 py-3" style="border-bottom:1px solid var(--border);">
                            <div style="width:48px; height:48px; border-radius:10px; overflow:hidden; flex-shrink:0; background:var(--brand-light);">
                                <?php if ($imgSrc): ?>
                                    <img src="<?= $imgSrc ?>" alt="<?= e($item['product_name']) ?>"
                                         style="width:100%; height:100%; object-fit:cover;">
                                <?php else: ?>
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                         style="color:var(--brand-primary);">
                                        <i class="bi bi-box-seam"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold" style="font-size:0.9rem;"><?= e($item['product_name']) ?></div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    <?= (int) $item['quantity'] ?> × <?= money($item['unit_price']) ?>
                                </div>
                            </div>
                            <div class="fw-bold text-success">
                                <?= money((float) $item['unit_price'] * (int) $item['quantity']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Notification Message -->
            <div class="alert border-0 mb-4 rounded-3"
                 style="background:rgba(59,130,246,0.08); border-left:4px solid #3b82f6 !important; border-radius:12px!important;">
                <div class="d-flex align-items-start gap-3">
                    <i class="bi bi-bell-fill" style="color:#3b82f6; font-size:1.2rem; margin-top:2px;"></i>
                    <div>
                        <div class="fw-semibold mb-1" style="color:#1e40af;">You're all set!</div>
                        <div class="text-muted" style="font-size:0.85rem;">
                            Your order has been received and our team has been notified.
                            You can track your order status from the <strong>My Orders</strong> page.
                            We'll update your order status as it progresses.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="row g-3">
                <div class="col-sm-6">
                    <a href="orders.php" class="btn btn-primary-green w-100 py-3 fw-bold">
                        <i class="bi bi-bag-check me-2"></i> View My Orders
                    </a>
                </div>
                <div class="col-sm-6">
                    <a href="shop.php" class="btn btn-outline-green w-100 py-3 fw-bold">
                        <i class="bi bi-shop me-2"></i> Continue Shopping
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>