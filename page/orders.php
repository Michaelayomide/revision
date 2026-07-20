<?php
require_once __DIR__ . '/../config/init.php';
require_admin();
require_role('primary_admin', 'secondary_admin', 'editor');

$pageTitle = 'Manage Orders';
$orderStatuses = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'];
$shippingStatuses = ['Pending', 'In Transit', 'Out for Delivery', 'Delivered', 'Returned'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('orders.php');
    }

    $action = $_POST['action'] ?? '';
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $orderStatus = $_POST['order_status'] ?? '';
    $shippingStatus = $_POST['shipping_status'] ?? '';

    try {
        if ($action === 'update_status') {
            if ($orderId <= 0 || !in_array($orderStatus, $orderStatuses, true) || !in_array($shippingStatus, $shippingStatuses, true)) {
                flash('danger', 'Invalid order status selection.');
                redirect_to('orders.php');
            }

            // Fetch current order status to validate transitions
            $stmt = $database->prepare("SELECT order_status FROM orders WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $orderId]);
            $order = $stmt->fetch();
            if (!$order) {
                flash('danger', 'Order not found.');
                redirect_to('orders.php');
            }

            $currentStatus = $order['order_status'];

            // Role check: Only Super Admin can transition to/from Confirmed (Approved) or Cancelled
            $isApproveOrCancelAction = in_array($orderStatus, ['Confirmed', 'Cancelled'], true);
            $isAlteringApprovedOrCancelled = in_array($currentStatus, ['Confirmed', 'Cancelled'], true);

            if (($isApproveOrCancelAction || $isAlteringApprovedOrCancelled) && !is_primary_admin()) {
                flash('danger', 'Access Denied: Only Super Admins can approve, cancel, or modify approved/cancelled orders.');
                redirect_to('orders.php');
            }

            $stmt = $database->prepare(
                "UPDATE orders 
                 SET order_status = :order_status, shipping_status = :shipping_status 
                 WHERE id = :id"
            );
            $stmt->execute([
                'order_status' => $orderStatus,
                'shipping_status' => $shippingStatus,
                'id' => $orderId
            ]);
            flash('success', 'Order status updated successfully.');
        }
    } catch (PDOException $e) {
        flash('danger', 'Failed to update order status.');
    }

    redirect_to('orders.php');
}

$orders = [];
try {
    $stmt = $database->prepare("SELECT id, txn_id, customer_name, total_amount, order_status, shipping_status, created_at FROM orders ORDER BY id DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Orders could not be loaded. Please ensure tables are migrated.';
}

$flashMessages = consume_flash_messages();

$pageScripts = <<<'HTML'
<script>
function editOrderStatus(button) {
    document.getElementById('modalOrderId').value = button.dataset.id;
    document.getElementById('modalOrderTxn').textContent = button.dataset.txn;
    document.getElementById('modalOrderStatus').value = button.dataset.orderstatus;
    document.getElementById('modalShippingStatus').value = button.dataset.shippingstatus;
    new bootstrap.Modal(document.getElementById('orderModal')).show();
}
</script>
HTML;

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Orders</li>
                </ol>
            </nav>
            <h1 class="page-title">Orders Management</h1>
        </div>
    </div>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Txn ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Order Status</th>
                            <th>Shipping Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-semibold">#<?php echo e($order['id']); ?></td>
                                    <td><?php echo e($order['txn_id']); ?></td>
                                    <td><?php echo e($order['customer_name']); ?></td>
                                    <td class="cell-strong text-success"><?php echo e(money($order['total_amount'])); ?></td>
                                    <td><span class="badge bg-secondary status"><?php echo e($order['order_status']); ?></span></td>
                                    <td><span class="badge bg-info status"><?php echo e($order['shipping_status']); ?></span></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y H:i', strtotime($order['created_at']))); ?></td>
                                    <td>
                                        <?php 
                                        $canUpdate = true;
                                        if (in_array($order['order_status'], ['Confirmed', 'Cancelled'], true) && !is_primary_admin()) {
                                            $canUpdate = false;
                                        }
                                        ?>
                                        <?php if ($canUpdate): ?>
                                            <button 
                                                class="btn btn-sm btn-outline-primary"
                                                data-id="<?php echo e($order['id']); ?>"
                                                data-txn="<?php echo e($order['txn_id']); ?>"
                                                data-orderstatus="<?php echo e($order['order_status']); ?>"
                                                data-shippingstatus="<?php echo e($order['shipping_status']); ?>"
                                                onclick="editOrderStatus(this)">
                                                Update Status
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Locked</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-bag-x"></i>
                                        <div class="empty-title">No orders found</div>
                                        <div class="empty-text">New customer orders will appear here.</div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="modalOrderId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-truck text-primary"></i> Update Order Status <span class="text-muted" id="modalOrderTxn"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="modalOrderStatus">Order Status</label>
                        <select class="form-select" name="order_status" id="modalOrderStatus">
                            <?php foreach ($orderStatuses as $status): ?>
                                <?php 
                                $isDisabled = '';
                                if (in_array($status, ['Confirmed', 'Cancelled'], true) && !is_primary_admin()) {
                                    $isDisabled = 'disabled';
                                }
                                ?>
                                <option value="<?php echo e($status); ?>" <?php echo $isDisabled; ?>><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="modalShippingStatus">Shipping Status</label>
                        <select class="form-select" name="shipping_status" id="modalShippingStatus">
                            <?php foreach ($shippingStatuses as $status): ?>
                                <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>