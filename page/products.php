<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Products';
$productStatuses = ['Active', 'Inactive', 'Out of Stock'];
// Supported Product Categories
$productCategories = ['General', 'Electronics', 'Audio', 'Mobile Devices', 'Wearables'];

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
    
    // Captured Phase 1 extension payload variables
    $category = trim($_POST['category'] ?? 'General');
    $category = in_array($category, $productCategories, true) ? $category : 'General';
    $description = trim($_POST['description'] ?? '');

    if ($stockQuantity === 0 && $status === 'Active') {
        $status = 'Out of Stock';
    }

    try {
        // --- Image Upload Logic Handler ---
        $imagePath = null;
        $hasUpload = isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK;
        
        if ($hasUpload) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $fileSize = $_FILES['product_image']['size'];
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($fileExtension, $allowedExtensions, true)) {
                flash('danger', 'Invalid file type. Allowed variants: JPG, JPEG, PNG, WebP.');
                redirect_to('products.php');
            }

            if ($fileSize > 2 * 1024 * 1024) {
                flash('danger', 'File size exceeds maximum threshold safety limit of 2MB.');
                redirect_to('products.php');
            }

            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $newFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                $imagePath = 'uploads/products/' . $newFileName;
            }
        }

        if ($action === 'create') {
            if ($name === '' || $sku === '' || $price <= 0) {
                flash('danger', 'Name, SKU, and a positive price are required.');
                redirect_to('products.php');
            }

            $stmt = $database->prepare(
                "INSERT INTO products (name, description, image_path, category, sku, price, stock_quantity, status)
                 VALUES (:name, :description, :image_path, :category, :sku, :price, :stock_quantity, :status)"
            );
            $stmt->execute([
                'name' => $name,
                'description' => $description,
                'image_path' => $imagePath,
                'category' => $category,
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

            // If a new image was uploaded, update the image path column. Otherwise, keep the existing path.
            if ($imagePath !== null) {
                $stmt = $database->prepare(
                    "UPDATE products
                     SET name = :name, description = :description, image_path = :image_path, category = :category, sku = :sku, price = :price, stock_quantity = :stock_quantity, status = :status
                     WHERE id = :id"
                );
                $params = ['image_path' => $imagePath];
            } else {
                $stmt = $database->prepare(
                    "UPDATE products
                     SET name = :name, description = :description, category = :category, sku = :sku, price = :price, stock_quantity = :stock_quantity, status = :status
                     WHERE id = :id"
                );
                $params = [];
            }

            $baseParams = [
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'sku' => $sku,
                'price' => $price,
                'stock_quantity' => $stockQuantity,
                'status' => $status,
                'id' => $productId,
            ];

            $stmt->execute(array_merge($baseParams, $params));
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
        "SELECT id, name, description, image_path, category, sku, price, stock_quantity, status, created_at
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
    document.getElementById('imagePreview').src = '';
    document.getElementById('previewContainer').classList.add('d-none');
    document.getElementById('imageInput').required = true; // Image mandatory for creation
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
    document.getElementById('categoryInput').value = button.dataset.category || 'General';
    document.getElementById('descriptionInput').value = button.dataset.description || '';
    
    document.getElementById('imageInput').required = false; // Image optional during updates

    const previewContainer = document.getElementById('previewContainer');
    const imagePreview = document.getElementById('imagePreview');
    if (button.dataset.image && button.dataset.image !== '') {
        imagePreview.src = '../' + button.dataset.image;
        previewContainer.classList.remove('d-none');
    } else {
        imagePreview.src = '';
        previewContainer.classList.add('d-none');
    }

    new bootstrap.Modal(document.getElementById('productModal')).show();
}

function deleteProduct(id) {
    if (confirm('Delete this product? Linked orders may prevent deletion.')) {
        document.getElementById('deleteProductId').value = id;
        document.getElementById('deleteProductForm').submit();
    }
}

// Client-Side Image Sandbox Preview Engine Initializer
document.addEventListener('DOMContentLoaded', function() {
    const imgInput = document.getElementById('imageInput');
    if (imgInput) {
        imgInput.addEventListener('change', function(event) {
            const fileInput = event.target;
            const previewContainer = document.getElementById('previewContainer');
            const imagePreview = document.getElementById('imagePreview');

            if (fileInput.files && fileInput.files[0]) {
                const targetedFile = fileInput.files[0];
                const validatedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

                if (!validatedTypes.includes(targetedFile.type)) {
                    alert('Invalid configuration selected. Please upload a structured picture asset.');
                    fileInput.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }

                if (targetedFile.size > 2 * 1024 * 1024) {
                    alert('File configuration size payload exceeds safety threshold parameters (2MB limit).');
                    fileInput.value = '';
                    previewContainer.classList.add('d-none');
                    return;
                }

                const processingReader = new FileReader();
                processingReader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
                processingReader.readAsDataURL(targetedFile);
            }
        });
    }
});
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
                    <li class="breadcrumb-item active" aria-current="page">Products</li>
                </ol>
            </nav>
            <h1 class="page-title">Products</h1>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="prepareAddModal()">
                <i class="bi bi-plus-lg"></i> New Product
            </button>
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

    <div class="toolbar">
        <div class="toolbar-search">
            <i class="bi bi-search navbar-search-icon"></i>
            <input type="text" id="productSearchInput" name="product_search" class="form-control app-search" style="width:100%;" placeholder="Search by product name or SKU..." onkeyup="filterProducts()" aria-label="Search products">
        </div>
        <div class="toolbar-filters">
            <select class="form-select" id="statusFilter" name="product_status_filter" onchange="filterProducts()" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <?php foreach ($productStatuses as $status): ?>
                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th class="text-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products): ?>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $stockClass = (int) $product['stock_quantity'] === 0 ? 'text-danger fw-bold' : '';
                                $badgeClass = $product['status'] === 'Active' ? 'bg-success status' : ($product['status'] === 'Out of Stock' ? 'bg-danger status' : 'bg-secondary status');

                                // Dynamic placeholder logic if file does not exist
                                $imgUrl = (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path']))
                                    ? '../' . htmlspecialchars($product['image_path'])
                                    : 'https://via.placeholder.com/50x50?text=No+Img';
                                ?>
                                <tr class="product-row">
                                    <td class="fw-semibold">#<?php echo e($product['id']); ?></td>
                                    <td>
                                        <img src="<?php echo $imgUrl; ?>" class="table-avatar" style="width:42px;height:42px;" alt="Thumbnail" loading="lazy">
                                    </td>
                                    <td class="prod-name fw-medium"><?php echo e($product['name']); ?></td>
                                    <td><span class="badge bg-light border"><?php echo e($product['category'] ?? 'General'); ?></span></td>
                                    <td class="prod-sku text-uppercase font-monospace"><?php echo e($product['sku']); ?></td>
                                    <td class="cell-strong text-success"><?php echo e(money($product['price'])); ?></td>
                                    <td class="<?php echo e($stockClass); ?>"><?php echo e(number_format((int) $product['stock_quantity'])); ?> units</td>
                                    <td><span class="badge <?php echo e($badgeClass); ?> prod-status"><?php echo e($product['status']); ?></span></td>
                                    <td class="text-nowrap">
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-id="<?php echo e($product['id']); ?>"
                                            data-name="<?php echo e($product['name']); ?>"
                                            data-sku="<?php echo e($product['sku']); ?>"
                                            data-price="<?php echo e($product['price']); ?>"
                                            data-stock="<?php echo e($product['stock_quantity']); ?>"
                                            data-status="<?php echo e($product['status']); ?>"
                                            data-category="<?php echo e($product['category'] ?? 'General'); ?>"
                                            data-description="<?php echo e($product['description'] ?? ''); ?>"
                                            data-image="<?php echo e($product['image_path'] ?? ''); ?>"
                                            onclick="editProduct(this)">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo e((int) $product['id']); ?>)"><i class="bi bi-trash"></i> Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-box-seam"></i>
                                        <div class="empty-title">No products found</div>
                                        <div class="empty-text">Create your first product to populate the catalog.</div>
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

<div class="modal fade" id="productModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" id="productForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" id="formActionToken" value="create">
                <input type="hidden" name="product_id" id="formProductIdToken">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"><i class="bi bi-box-seam text-primary"></i> Create Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="nameInput">Product Name</label>
                            <input type="text" class="form-control" name="name" id="nameInput" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="categoryInput">Category Classification</label>
                            <select class="form-select" name="category" id="categoryInput">
                                <?php foreach ($productCategories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="descriptionInput">Product Description</label>
                            <textarea class="form-control" name="description" id="descriptionInput" rows="3" placeholder="Provide operational product specifications..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="skuInput">SKU Code</label>
                            <input type="text" class="form-control text-uppercase" name="sku" id="skuInput" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="priceInput">Price ($)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" id="priceInput" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="stockInput">Stock Quantity</label>
                            <input type="number" min="0" class="form-control" name="stock_quantity" id="stockInput" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="statusInput">Status State</label>
                            <select class="form-select" name="status" id="statusInput">
                                <?php foreach ($productStatuses as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="imageInput">Product Media Asset</label>
                            <input type="file" class="form-control" name="product_image" id="imageInput" accept="image/jpeg, image/jpg, image/png, image/webp">
                        </div>
                        
                        <div class="col-12 text-center d-none" id="previewContainer">
                            <label class="form-label d-block text-start fw-semibold text-secondary">Asset Sandbox Render Frame:</label>
                            <img id="imagePreview" src="#" alt="Rendering sandbox container preview window" class="img-thumbnail rounded shadow-sm" style="max-height: 160px; object-fit: contain;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">Save Product</button>
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