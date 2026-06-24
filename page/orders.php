<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Orders';
$orderStatuses = ['Pending', 'Processing', 'Completed', 'Cancelled'];
$shippingStatuses = ['Processing', 'Packed', 'Shipped', 'Delivered', 'Returned'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('orders.php');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_order') {
            $customerName = trim($_POST['customer_name'] ?? '');
            $productId = (int) ($_POST['product_id'] ?? 0);
            $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

            if ($customerName === '' || $productId <= 0) {
                flash('danger', 'Customer name and product selection are required.');
                redirect_to('orders.php');
            }

            $database->beginTransaction();

            $stmt = $database->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $database->rollBack();
                flash('danger', 'Selected product was not found.');
                redirect_to('orders.php');
            }

            if ((int) $product['stock_quantity'] < $quantity) {
                $database->rollBack();
                flash('danger', 'Insufficient stock for ' . $product['name'] . '.');
                redirect_to('orders.php');
            }

            $unitPrice = (float) $product['price'];
            $totalAmount = $unitPrice * $quantity;
            $txnId = 'TR-' . date('YmdHis') . '-' . random_int(100, 999);

            $orderStmt = $database->prepare(
                "INSERT INTO orders (txn_id, customer_name, total_amount, order_status, shipping_status)
                 VALUES (:txn_id, :customer_name, :total_amount, 'Pending', 'Processing')"
            );
            $orderStmt->execute([
                'txn_id' => $txnId,
                'customer_name' => $customerName,
                'total_amount' => $totalAmount,
            ]);

            $orderId = (int) $database->lastInsertId();

            $itemStmt = $database->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity, unit_price)
                 VALUES (:order_id, :product_id, :quantity, :unit_price)"
            );
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);

            $newStock = (int) $product['stock_quantity'] - $quantity;
            $newProductStatus = $newStock === 0 ? 'Out of Stock' : 'Active';
            $stockStmt = $database->prepare(
                "UPDATE products
                 SET stock_quantity = :stock_quantity, status = :status
                 WHERE id = :id"
            );
            $stockStmt->execute([
                'stock_quantity' => $newStock,
                'status' => $newProductStatus,
                'id' => $productId,
            ]);

            $database->commit();
            flash('success', 'Order created and inventory updated.');
        } elseif ($action === 'update_status') {
            $orderId = (int) ($_POST['order_id'] ?? 0);
            $orderStatus = $_POST['order_status'] ?? 'Pending';
            $shippingStatus = $_POST['shipping_status'] ?? 'Processing';

            if ($orderId <= 0 || !in_array($orderStatus, $orderStatuses, true) || !in_array($shippingStatus, $shippingStatuses, true)) {
                flash('danger', 'Invalid order status update.');
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
                'id' => $orderId,
            ]);
            flash('success', 'Order status updated.');
        } elseif ($action === 'delete') {
            $orderId = (int) ($_POST['order_id'] ?? 0);
            if ($orderId <= 0) {
                flash('danger', 'Invalid order selected.');
                redirect_to('orders.php');
            }

            $database->beginTransaction();
            $stmt = $database->prepare('DELETE FROM order_items WHERE order_id = :order_id');
            $stmt->execute(['order_id' => $orderId]);
            $stmt = $database->prepare('DELETE FROM orders WHERE id = :id');
            $stmt->execute(['id' => $orderId]);
            $database->commit();
            flash('success', 'Order deleted.');
        }
    } catch (PDOException $e) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        flash('danger', 'Order operation failed.');
    }

    redirect_to('orders.php');
}

$products = [];
$orders = [];
$loadError = '';

try {
    $stmt = $database->prepare(
        "SELECT id, name, sku, price, stock_quantity
         FROM products
         WHERE status = 'Active' AND stock_quantity > 0
         ORDER BY name ASC"
    );
    $stmt->execute();
    $products = $stmt->fetchAll();

    $stmt = $database->prepare(
        "SELECT
            o.id,
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
         GROUP BY o.id, o.txn_id, o.customer_name, o.total_amount, o.order_status, o.shipping_status, o.created_at
         ORDER BY o.created_at DESC, o.id DESC"
    );
    $stmt->execute();
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Orders could not be loaded. Install or migrate the orders and order_items schema.';
}

$flashMessages = consume_flash_messages();

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary mb-0">Orders</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal">
            <i class="bi bi-plus-lg me-2"></i> New Order
        </button>
    </div>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if ($loadError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Txn ID</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Order Status</th>
                            <th>Shipping</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($order['txn_id']); ?></td>
                                    <td><?php echo e($order['customer_name']); ?></td>
                                    <td><?php echo e($order['items'] ?? 'No items'); ?></td>
                                    <td><?php echo e(money($order['total_amount'])); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo e($order['order_status']); ?></span></td>
                                    <td><span class="badge bg-info"><?php echo e($order['shipping_status']); ?></span></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y H:i', strtotime($order['created_at']))); ?></td>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <form method="POST" class="d-inline-flex gap-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="order_id" value="<?php echo e($order['id']); ?>">
                                                <select name="order_status" class="form-select form-select-sm">
                                                    <?php foreach ($orderStatuses as $status): ?>
                                                        <option value="<?php echo e($status); ?>" <?php echo $order['order_status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="shipping_status" class="form-select form-select-sm">
                                                    <?php foreach ($shippingStatuses as $status): ?>
                                                        <option value="<?php echo e($status); ?>" <?php echo $order['shipping_status'] === $status ? 'selected' : ''; ?>><?php echo e($status); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Delete this order record?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="order_id" value="<?php echo e($order['id']); ?>">
                                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No orders captured yet.</td>
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
                <input type="hidden" name="action" value="create_order">
                <div class="modal-header">
                    <h5 class="modal-title">Create Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="customerName">Customer Name</label>
                        <input type="text" class="form-control" name="customer_name" id="customerName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="productId">Product</label>
                        <select class="form-select" name="product_id" id="productId" required>
                            <option value="">Select a product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo e($product['id']); ?>">
                                    <?php echo e($product['name'] . ' [' . $product['sku'] . '] - ' . money($product['price']) . ' / ' . $product['stock_quantity'] . ' in stock'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="quantity">Quantity</label>
                        <input type="number" min="1" class="form-control" name="quantity" id="quantity" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
