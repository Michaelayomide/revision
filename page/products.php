<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Products';
$productStatuses = ['Active', 'Inactive', 'Out of Stock'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('products.php');
    }

    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $price = (float) ($_POST['price'] ?? 0);
    $stockQuantity = max(0, (int) ($_POST['stock_quantity'] ?? 0));
    $status = $_POST['status'] ?? 'Active';
    $status = in_array($status, $productStatuses, true) ? $status : 'Active';

    if ($stockQuantity === 0 && $status === 'Active') {
        $status = 'Out of Stock';
    }

    try {
        if ($action === 'create') {
            if ($name === '' || $sku === '' || $price <= 0) {
                flash('danger', 'Name, SKU, and a positive price are required.');
                redirect_to('products.php');
            }

            $stmt = $database->prepare(
                "INSERT INTO products (name, sku, price, stock_quantity, status)
                 VALUES (:name, :sku, :price, :stock_quantity, :status)"
            );
            $stmt->execute([
                'name' => $name,
                'sku' => $sku,
                'price' => $price,
                'stock_quantity' => $stockQuantity,
                'status' => $status,
            ]);
            flash('success', 'Product created successfully.');
        } elseif ($action === 'update') {
            if ($productId <= 0 || $name === '' || $sku === '' || $price <= 0) {
                flash('danger', 'A valid product, name, SKU, and price are required.');
                redirect_to('products.php');
            }

            $stmt = $database->prepare(
                "UPDATE products
                 SET name = :name, sku = :sku, price = :price, stock_quantity = :stock_quantity, status = :status
                 WHERE id = :id"
            );
            $stmt->execute([
                'name' => $name,
                'sku' => $sku,
                'price' => $price,
                'stock_quantity' => $stockQuantity,
                'status' => $status,
                'id' => $productId,
            ]);
            flash('success', 'Product updated successfully.');
        } elseif ($action === 'delete') {
            if ($productId <= 0) {
                flash('danger', 'Invalid product selected.');
                redirect_to('products.php');
            }

            $stmt = $database->prepare('DELETE FROM products WHERE id = :id');
            $stmt->execute(['id' => $productId]);
            flash('success', 'Product deleted successfully.');
        }
    } catch (PDOException $e) {
        flash('danger', 'Product database operation failed. Check for duplicate SKU values or linked order items.');
    }

    redirect_to('products.php');
}

$products = [];
$loadError = '';

try {
    $stmt = $database->prepare(
        "SELECT id, name, sku, price, stock_quantity, status, created_at
         FROM products
         ORDER BY id DESC"
    );
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Products could not be loaded. Install or migrate the products table schema.';
}

$flashMessages = consume_flash_messages();

$pageScripts = <<<'HTML'
<script>
function filterProducts() {
    const searchVal = document.getElementById('productSearchInput').value.toLowerCase();
    const statusVal = document.getElementById('statusFilter').value;
    document.querySelectorAll('.product-row').forEach(row => {
        const name = row.querySelector('.prod-name').textContent.toLowerCase();
        const sku = row.querySelector('.prod-sku').textContent.toLowerCase();
        const status = row.querySelector('.prod-status').textContent.trim();
        row.style.display = ((name.includes(searchVal) || sku.includes(searchVal)) && (!statusVal || status === statusVal)) ? '' : 'none';
    });
}

function prepareAddModal() {
    document.getElementById('modalTitle').textContent = 'Create Product';
    document.getElementById('formActionToken').value = 'create';
    document.getElementById('formProductIdToken').value = '';
    document.getElementById('productForm').reset();
}

function editProduct(button) {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('formActionToken').value = 'update';
    document.getElementById('formProductIdToken').value = button.dataset.id;
    document.getElementById('nameInput').value = button.dataset.name;
    document.getElementById('skuInput').value = button.dataset.sku;
    document.getElementById('priceInput').value = button.dataset.price;
    document.getElementById('stockInput').value = button.dataset.stock;
    document.getElementById('statusInput').value = button.dataset.status;
    new bootstrap.Modal(document.getElementById('productModal')).show();
}

function deleteProduct(id) {
    if (confirm('Delete this product? Linked orders may prevent deletion.')) {
        document.getElementById('deleteProductId').value = id;
        document.getElementById('deleteProductForm').submit();
    }
}
</script>
HTML;

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary mb-0">Products</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="prepareAddModal()">
            <i class="bi bi-plus-lg me-2"></i> New Product
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

    <form method="POST" onsubmit="event.preventDefault();" class="row g-3 mb-4">
        <div class="col-md-8">
            <input type="text" id="productSearchInput" name="product_search" class="form-control" placeholder="Search by product name or SKU..." onkeyup="filterProducts()">
        </div>
        <div class="col-md-4">
            <select class="form-select" id="statusFilter" name="product_status_filter" onchange="filterProducts()">
                <option value="">All Statuses</option>
                <?php foreach ($productStatuses as $status): ?>
                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products): ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stockClass = (int) $product['stock_quantity'] === 0 ? 'text-danger' : 'text-dark';
                                $badgeClass = $product['status'] === 'Active' ? 'bg-success' : ($product['status'] === 'Out of Stock' ? 'bg-danger' : 'bg-secondary');
                                ?>
                                <tr class="product-row">
                                    <td class="fw-semibold">#<?php echo e($product['id']); ?></td>
                                    <td class="prod-name fw-medium"><?php echo e($product['name']); ?></td>
                                    <td class="prod-sku"><?php echo e($product['sku']); ?></td>
                                    <td><?php echo e(money($product['price'])); ?></td>
                                    <td class="<?php echo e($stockClass); ?>"><?php echo e(number_format((int) $product['stock_quantity'])); ?> units</td>
                                    <td><span class="badge <?php echo e($badgeClass); ?> prod-status"><?php echo e($product['status']); ?></span></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y', strtotime($product['created_at']))); ?></td>
                                    <td class="text-nowrap">
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-id="<?php echo e($product['id']); ?>"
                                            data-name="<?php echo e($product['name']); ?>"
                                            data-sku="<?php echo e($product['sku']); ?>"
                                            data-price="<?php echo e($product['price']); ?>"
                                            data-stock="<?php echo e($product['stock_quantity']); ?>"
                                            data-status="<?php echo e($product['status']); ?>"
                                            onclick="editProduct(this)">
                                            Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo e((int) $product['id']); ?>)">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="productForm">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" id="formActionToken" value="create">
                <input type="hidden" name="product_id" id="formProductIdToken">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Create Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="nameInput">Product Name</label>
                        <input type="text" class="form-control" name="name" id="nameInput" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="skuInput">SKU</label>
                        <input type="text" class="form-control" name="sku" id="skuInput" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="priceInput">Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" id="priceInput" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="stockInput">Stock Quantity</label>
                            <input type="number" min="0" class="form-control" name="stock_quantity" id="stockInput" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="statusInput">Status</label>
                        <select class="form-select" name="status" id="statusInput">
                            <?php foreach ($productStatuses as $status): ?>
                                <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="POST" id="deleteProductForm" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="product_id" id="deleteProductId">
</form>

<?php include __DIR__ . '/../components/footer.php'; ?>
