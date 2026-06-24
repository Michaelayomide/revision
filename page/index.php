<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Dashboard';
$admin_display_name = $_SESSION['admin_name'] ?? 'Admin User';

$metrics = [
    'total_revenue' => 0,
    'system_stock' => 0,
    'pending_orders' => 0,
    'total_products' => 0,
];
$recentOrders = [];
$dbError = '';

try {
    $queries = [
        'total_revenue' => "SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE order_status <> 'Cancelled'",
        'system_stock' => "SELECT COALESCE(SUM(stock_quantity), 0) FROM products",
        'pending_orders' => "SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'",
        'total_products' => "SELECT COUNT(*) FROM products",
    ];

    foreach ($queries as $key => $sql) {
        $stmt = $database->prepare($sql);
        $stmt->execute();
        $metrics[$key] = $stmt->fetchColumn();
    }

    $stmt = $database->prepare(
        "SELECT txn_id, customer_name, total_amount, order_status, created_at
         FROM orders
         ORDER BY created_at DESC, id DESC
         LIMIT 5"
    );
    $stmt->execute();
    $recentOrders = $stmt->fetchAll();
} catch (PDOException $e) {
    $dbError = 'Dashboard metrics are unavailable until the AdminHub schema is installed.';
}

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="welcome-header">
        <h2 class="fw-bold mb-1"><?php echo e($greeting . ', ' . $admin_display_name); ?></h2>
        <p class="mb-0 opacity-90">Live operating metrics from your catalog, stock, and order workflow.</p>
    </div>

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><?php echo e($dbError); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body">
                    <i class="bi bi-currency-dollar fs-1 mb-3"></i>
                    <h5>Total Revenue</h5>
                    <h2 class="fw-bold"><?php echo e(money($metrics['total_revenue'])); ?></h2>
                    <small class="opacity-90">Completed and active order value</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <div class="card-body">
                    <i class="bi bi-boxes fs-1 mb-3"></i>
                    <h5>System Stock</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $metrics['system_stock'])); ?></h2>
                    <small class="opacity-90">Total catalog units available</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <i class="bi bi-clock-history fs-1 mb-3"></i>
                    <h5>Pending Orders</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $metrics['pending_orders'])); ?></h2>
                    <small class="opacity-90">Awaiting processing</small>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <div class="card-body">
                    <i class="bi bi-box-seam fs-1 mb-3"></i>
                    <h5>Total Products</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $metrics['total_products'])); ?></h2>
                    <small class="opacity-90">Catalog records</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold py-3 border-bottom">Recent Orders</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Txn ID</th>
                                    <th>Customer</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentOrders): ?>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo e($order['txn_id']); ?></td>
                                            <td><?php echo e($order['customer_name']); ?></td>
                                            <td><?php echo e(money($order['total_amount'])); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo e($order['order_status']); ?></span></td>
                                            <td class="small text-muted"><?php echo e(date('M d, Y H:i', strtotime($order['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No orders captured yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header bg-white fw-bold py-3">Quick Actions</div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="products.php" class="btn btn-outline-info btn-lg">
                            <i class="bi bi-box-seam me-2"></i> Manage Products
                        </a>
                        <a href="stock.php" class="btn btn-outline-success btn-lg">
                            <i class="bi bi-boxes me-2"></i> Adjust Stock
                        </a>
                        <a href="orders.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-bag-check me-2"></i> Create Order
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
