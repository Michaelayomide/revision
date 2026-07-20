<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Stock';
$stockActions = ['add', 'remove', 'set'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('stock.php');
    }

    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'stock_movement') {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $movementType = $_POST['movement_type'] ?? 'set';
            $quantity = max(0, (int) ($_POST['quantity'] ?? 0));

            if ($productId <= 0 || !in_array($movementType, $stockActions, true)) {
                flash('danger', 'Invalid stock movement request.');
                redirect_to('stock.php');
            }

            $database->beginTransaction();

            $stmt = $database->prepare('SELECT stock_quantity FROM products WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $database->rollBack();
                flash('danger', 'Product was not found.');
                redirect_to('stock.php');
            }

            $currentStock = (int) $product['stock_quantity'];
            if ($movementType === 'add') {
                $newStock = $currentStock + $quantity;
            } elseif ($movementType === 'remove') {
                $newStock = max(0, $currentStock - $quantity);
            } else {
                $newStock = $quantity;
            }

            $newStatus = $newStock === 0 ? 'Out of Stock' : 'Active';
            $update = $database->prepare(
                'UPDATE products SET stock_quantity = :stock_quantity, status = :status WHERE id = :id'
            );
            $update->execute([
                'stock_quantity' => $newStock,
                'status' => $newStatus,
                'id' => $productId,
            ]);

            $database->commit();
            flash('success', 'Stock quantity updated successfully.');
        }
    } catch (PDOException $e) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }
        flash('danger', 'Stock update failed.');
    }

    redirect_to('stock.php');
}

$summary = [
    'stock_value' => 0,
    'stock_units' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
];
$products = [];
$loadError = '';

try {
    $stmt = $database->prepare(
        "SELECT
            COALESCE(SUM(price * stock_quantity), 0) AS stock_value,
            COALESCE(SUM(stock_quantity), 0) AS stock_units,
            COALESCE(SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 10 THEN 1 ELSE 0 END), 0) AS low_stock,
            COALESCE(SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END), 0) AS out_of_stock
         FROM products"
    );
    $stmt->execute();
    $summary = array_merge($summary, $stmt->fetch() ?: []);

    $stmt = $database->prepare(
        "SELECT id, name, sku, price, stock_quantity, status, created_at
         FROM products
         ORDER BY stock_quantity ASC, name ASC"
    );
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Stock data could not be loaded. Install or migrate the products table schema.';
}

$flashMessages = consume_flash_messages();

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
                    <li class="breadcrumb-item active" aria-current="page">Stock</li>
                </ol>
            </nav>
            <h1 class="page-title">Stock &amp; Inventory</h1>
        </div>
        <div class="page-header-actions">
            <a href="products.php" class="btn btn-outline-primary">
                <i class="bi bi-plus-circle"></i> Manage Catalog
            </a>
        </div>
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

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-currency-dollar"></i></span>
                    <span class="stat-label">Total Stock Value</span>
                    <span class="stat-value"><?php echo e(money($summary['stock_value'])); ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-boxes"></i></span>
                    <span class="stat-label">Total Units</span>
                    <span class="stat-value"><?php echo e(number_format((float) $summary['stock_units'])); ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
                    <span class="stat-label">Low Stock Items</span>
                    <span class="stat-value"><?php echo e(number_format((float) $summary['low_stock'])); ?></span>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <div class="card-body">
                    <span class="stat-icon"><i class="bi bi-x-circle"></i></span>
                    <span class="stat-label">Out of Stock</span>
                    <span class="stat-value"><?php echo e(number_format((float) $summary['out_of_stock'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <span class="card-title">Current Stock Levels</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Current Stock</th>
                            <th>Status</th>
                            <th>Stock Movement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products): ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stockQuantity = (int) $product['stock_quantity'];
                                $badgeClass = $stockQuantity === 0 ? 'bg-danger status' : ($stockQuantity <= 10 ? 'bg-warning status' : 'bg-success status');
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($product['sku']); ?></td>
                                    <td><?php echo e($product['name']); ?></td>
                                    <td><?php echo e(money($product['price'])); ?></td>
                                    <td><span class="badge <?php echo e($badgeClass); ?>"><?php echo e(number_format($stockQuantity)); ?></span></td>
                                    <td><?php echo e($product['status']); ?></td>
                                    <td>
                                        <form method="POST" class="row g-2 align-items-center">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="stock_movement">
                                            <input type="hidden" name="product_id" value="<?php echo e($product['id']); ?>">
                                            <div class="col-md-4">
                                                <select name="movement_type" class="form-select form-select-sm">
                                                    <option value="add">Add</option>
                                                    <option value="remove">Remove</option>
                                                    <option value="set">Set</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="number" min="0" name="quantity" class="form-control form-control-sm" value="0">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-sm btn-outline-primary w-100" type="submit">Apply</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="bi bi-box"></i>
                                        <div class="empty-title">No stock records found</div>
                                        <div class="empty-text">Add products to start tracking inventory.</div>
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

<?php include __DIR__ . '/../components/footer.php'; ?>
