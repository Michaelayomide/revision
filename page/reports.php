<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Reports';
$orderStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
$fromDate = trim($_GET['from_date'] ?? '');
$toDate = trim($_GET['to_date'] ?? '');
$selectedStatus = $_GET['order_status'] ?? '';

$conditions = [];
$params = [];

if ($fromDate !== '') {
    $conditions[] = 'o.created_at >= :from_date';
    $params['from_date'] = $fromDate . ' 00:00:00';
}

if ($toDate !== '') {
    $conditions[] = 'o.created_at <= :to_date';
    $params['to_date'] = $toDate . ' 23:59:59';
}

if (in_array($selectedStatus, $orderStatuses, true)) {
    $conditions[] = 'o.order_status = :order_status';
    $params['order_status'] = $selectedStatus;
} else {
    $selectedStatus = '';
}

$whereSql = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$metrics = [
    'total_revenue' => 0,
    'total_orders' => 0,
    'avg_order_value' => 0,
    'pending_shipments' => 0,
];
$transactions = [];
$loadError = '';

try {
    $stmt = $database->prepare(
        "SELECT
            COALESCE(SUM(o.total_amount), 0) AS total_revenue,
            COUNT(*) AS total_orders,
            COALESCE(AVG(o.total_amount), 0) AS avg_order_value,
            SUM(CASE WHEN o.shipping_status <> 'Delivered' AND o.order_status <> 'Cancelled' THEN 1 ELSE 0 END) AS pending_shipments
         FROM orders o
         {$whereSql}"
    );
    $stmt->execute($params);
    $metrics = array_merge($metrics, $stmt->fetch() ?: []);

    $stmt = $database->prepare(
        "SELECT
            o.txn_id,
            o.customer_name,
            o.total_amount,
            o.order_status,
            o.shipping_status,
            o.created_at,
            GROUP_CONCAT(CONCAT(COALESCE(p.name, 'Deleted Product'), ' x', oi.quantity) ORDER BY oi.id SEPARATOR ', ') AS items
         FROM orders o
         LEFT JOIN order_items oi ON oi.order_id = o.id
         LEFT JOIN products p ON p.id = oi.product_id
         {$whereSql}
         GROUP BY o.id, o.txn_id, o.customer_name, o.total_amount, o.order_status, o.shipping_status, o.created_at
         ORDER BY o.created_at DESC, o.id DESC"
    );
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Reports could not be loaded. Install or migrate the orders schema.';
}

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <h1 class="fw-bold text-primary mb-4">Sales & Transaction Reports</h1>

    <?php if ($loadError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body">
                    <i class="bi bi-currency-dollar fs-1 mb-3"></i>
                    <h5>Total Revenue</h5>
                    <h2 class="fw-bold"><?php echo e(money($metrics['total_revenue'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <div class="card-body">
                    <i class="bi bi-cart-check fs-1 mb-3"></i>
                    <h5>Total Orders</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $metrics['total_orders'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <i class="bi bi-graph-up fs-1 mb-3"></i>
                    <h5>Avg Order Value</h5>
                    <h2 class="fw-bold"><?php echo e(money($metrics['avg_order_value'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <div class="card-body">
                    <i class="bi bi-truck fs-1 mb-3"></i>
                    <h5>Pending Shipments</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $metrics['pending_shipments'])); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="fromDate">From Date</label>
                    <input type="date" class="form-control" id="fromDate" name="from_date" value="<?php echo e($fromDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="toDate">To Date</label>
                    <input type="date" class="form-control" id="toDate" name="to_date" value="<?php echo e($toDate); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="orderStatus">Status</label>
                    <select class="form-select" id="orderStatus" name="order_status">
                        <option value="">All</option>
                        <?php foreach ($orderStatuses as $status): ?>
                            <option value="<?php echo e($status); ?>" <?php echo $selectedStatus === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100" type="submit">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            Transaction History
            <a class="btn btn-outline-secondary btn-sm" href="orders.php"><i class="bi bi-bag-check me-1"></i> Manage Orders</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Txn ID</th>
                            <th>Date & Time</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Shipping</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions): ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($transaction['txn_id']); ?></td>
                                    <td><?php echo e(date('M d, Y H:i', strtotime($transaction['created_at']))); ?></td>
                                    <td><?php echo e($transaction['customer_name']); ?></td>
                                    <td><?php echo e($transaction['items'] ?? 'No items'); ?></td>
                                    <td class="fw-bold text-success"><?php echo e(money($transaction['total_amount'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo e($transaction['order_status']); ?></span></td>
                                    <td><span class="badge bg-info"><?php echo e($transaction['shipping_status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No transactions match the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
