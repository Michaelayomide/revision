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
            SUM(CASE WHEN stock_quantity > 0 AND stock_quantity <= 10 THEN 1 ELSE 0 END) AS low_stock,
            SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary mb-0">Stock & Inventory</h1>
        <a href="products.php" class="btn btn-outline-primary">
            <i class="bi bi-plus-circle me-2"></i> Manage Catalog
        </a>
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

    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="card-body">
                    <i class="bi bi-currency-dollar fs-1 mb-3"></i>
                    <h5>Total Stock Value</h5>
                    <h2 class="fw-bold"><?php echo e(money($summary['stock_value'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                <div class="card-body">
                    <i class="bi bi-boxes fs-1 mb-3"></i>
                    <h5>Total Units</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $summary['stock_units'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                    <h5>Low Stock Items</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $summary['low_stock'])); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                <div class="card-body">
                    <i class="bi bi-x-circle fs-1 mb-3"></i>
                    <h5>Out of Stock</h5>
                    <h2 class="fw-bold"><?php echo e(number_format((float) $summary['out_of_stock'])); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white fw-bold">Current Stock Levels</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
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
                                $badgeClass = $stockQuantity === 0 ? 'bg-danger' : ($stockQuantity <= 10 ? 'bg-warning text-dark' : 'bg-success');
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
                                <td colspan="6" class="text-center py-5 text-muted">No stock records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
