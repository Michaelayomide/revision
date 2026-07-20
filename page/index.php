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
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
            <h1 class="page-title"><?php echo e($greeting . ', ' . $admin_display_name); ?></h1>
        </div>
        <div class="page-header-actions">
            <a href="orders.php" class="btn btn-primary"><i class="bi bi-bag-check"></i> View Orders</a>
        </div>
    </div>

    <div class="welcome-header">
        <div>
            <h2>Live operating overview</h2>
            <p>Key metrics from your catalog, stock, and order workflow at a glance.</p>
        </div>
        <i class="bi bi-speedometer2" style="font-size: 2.6rem; opacity: 0.85;"></i>
    </div>

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><i class="bi bi-exclamation-triangle"></i> <?php echo e($dbError); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-currency-dollar"></i></span>
                    <span class="stat-label">Total Revenue</span>
                    <span class="stat-value"><?php echo e(money($metrics['total_revenue'])); ?></span>
                    <span class="stat-meta">Completed and active orders</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-boxes"></i></span>
                    <span class="stat-label">System Stock</span>
                    <span class="stat-value"><?php echo e(number_format((float) $metrics['system_stock'])); ?></span>
                    <span class="stat-meta">Total catalog units</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-clock-history"></i></span>
                    <span class="stat-label">Pending Orders</span>
                    <span class="stat-value"><?php echo e(number_format((float) $metrics['pending_orders'])); ?></span>
                    <span class="stat-meta">Awaiting processing</span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-box-seam"></i></span>
                    <span class="stat-label">Total Products</span>
                    <span class="stat-value"><?php echo e(number_format((float) $metrics['total_products'])); ?></span>
                    <span class="stat-meta">Catalog records</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header">
                    <span class="card-title">Recent Orders</span>
                    <a href="orders.php" class="btn btn-sm btn-outline-secondary">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
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
                                            <td class="cell-strong text-success"><?php echo e(money($order['total_amount'])); ?></td>
                                            <td><span class="badge bg-secondary status"><?php echo e($order['order_status']); ?></span></td>
                                            <td class="small text-muted"><?php echo e(date('M d, Y H:i', strtotime($order['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5">
                                            <div class="empty-state">
                                                <i class="bi bi-inbox"></i>
                                                <div class="empty-title">No orders captured yet</div>
                                                <div class="empty-text">New orders will appear here in real time.</div>
                                            </div>
                                        </td>
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
                <div class="card-header"><span class="card-title">Quick Actions</span></div>
                <div class="card-body d-flex flex-column gap-3">
                    <a href="products.php" class="quick-action">
                        <span class="qa-icon"><i class="bi bi-box-seam"></i></span>
                        <span><span class="qa-label d-block">Manage Products</span><span class="qa-sub">Add, edit, and organize your catalog</span></span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="stock.php" class="quick-action">
                        <span class="qa-icon"><i class="bi bi-boxes"></i></span>
                        <span><span class="qa-label d-block">Adjust Stock</span><span class="qa-sub">Update inventory and availability</span></span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    <a href="orders.php" class="quick-action">
                        <span class="qa-icon"><i class="bi bi-bag-check"></i></span>
                        <span><span class="qa-label d-block">Review Orders</span><span class="qa-sub">Track and process customer orders</span></span>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
