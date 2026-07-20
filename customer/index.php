<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'Dashboard';
$customerId = (int) $_SESSION['customer_id'];

// Stats
$totalOrders = 0;
$pendingOrders = 0;
$totalSpent = 0.0;
$cartItems = 0;

try {
    $s = $database->prepare("SELECT COUNT(*), SUM(total_amount) FROM orders WHERE customer_id = :cid");
    $s->execute(['cid' => $customerId]);
    [$totalOrders, $totalSpent] = $s->fetch(PDO::FETCH_NUM);
    $totalOrders = (int) $totalOrders;
    $totalSpent  = (float) ($totalSpent ?? 0);

    $s2 = $database->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = :cid AND order_status = 'Pending'");
    $s2->execute(['cid' => $customerId]);
    $pendingOrders = (int) $s2->fetchColumn();

    $s3 = $database->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE customer_id = :cid");
    $s3->execute(['cid' => $customerId]);
    $cartItems = (int) $s3->fetchColumn();
} catch (\Throwable) {}

// Recent orders
$recentStmt = $database->prepare(
    "SELECT * FROM orders WHERE customer_id = :cid ORDER BY created_at DESC LIMIT 5"
);
$recentStmt->execute(['cid' => $customerId]);
$recentOrders = $recentStmt->fetchAll();

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">Welcome back, <?= e(explode(' ', $_SESSION['customer_name'] ?? 'Customer')[0]) ?>! 👋</h2>
        <p class="text-muted mb-0">Here's a summary of your account.</p>
    </div>

    <!-- Stats -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.12);">
                    <i class="bi bi-bag-check" style="color:var(--brand-primary);"></i>
                </div>
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= $totalOrders ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.12);">
                    <i class="bi bi-clock" style="color:#f59e0b;"></i>
                </div>
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value"><?= $pendingOrders ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.12);">
                    <i class="bi bi-currency-dollar" style="color:#3b82f6;"></i>
                </div>
                <div class="stat-label">Total Spent</div>
                <div class="stat-value" style="font-size:1.4rem;"><?= money($totalSpent) ?></div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.12);">
                    <i class="bi bi-cart3" style="color:#8b5cf6;"></i>
                </div>
                <div class="stat-label">Items in Cart</div>
                <div class="stat-value"><?= $cartItems ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-lg-8">
            <div class="stat-card">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h5 class="fw-bold mb-0">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-green" style="font-size:0.8rem;">View All</a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bag-x fs-3 d-block mb-2"></i>
                        No orders yet. <a href="shop.php" class="text-link-green">Start shopping!</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recentOrders as $ord): ?>
                            <?php
                                $statusClass = match($ord['order_status']) {
                                    'Delivered' => 'status-delivered',
                                    'Shipped', 'Out for Delivery' => 'status-shipped',
                                    'Confirmed' => 'status-confirmed',
                                    'Processing' => 'status-processing',
                                    'Cancelled' => 'status-cancelled',
                                    default => 'status-pending',
                                };
                            ?>
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-3"
                                 style="background:var(--surface-2); border:1px solid var(--border);">
                                <div>
                                    <div class="fw-semibold" style="font-size:0.9rem;">
                                        <?= e($ord['txn_id'] ?? '#' . $ord['id']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.78rem;">
                                        <?= date('M j, Y', strtotime($ord['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="status-badge <?= $statusClass ?>" style="font-size:0.72rem;">
                                        <?= e($ord['order_status']) ?>
                                    </span>
                                    <span class="fw-bold text-success" style="font-size:0.9rem;">
                                        <?= money($ord['total_amount']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="stat-card">
                <h5 class="fw-bold mb-4">Quick Actions</h5>
                <div class="d-grid gap-3">
                    <a href="shop.php" class="btn btn-primary-green py-3">
                        <i class="bi bi-shop me-2"></i> Browse Products
                    </a>
                    <a href="cart.php" class="btn btn-outline-green py-2">
                        <i class="bi bi-cart3 me-2"></i> View Cart
                        <?= $cartItems > 0 ? "({$cartItems} items)" : '' ?>
                    </a>
                    <a href="orders.php" class="btn btn-outline-green py-2">
                        <i class="bi bi-bag-check me-2"></i> My Orders
                    </a>
                    <a href="notifications.php" class="btn btn-outline-green py-2">
                        <i class="bi bi-bell me-2"></i> Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
