<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle = 'Shop';

// ── Filters & Pagination ──────────────────────────────────────────────────────
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$perPage  = 12;
$page     = max(1, (int) ($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

// Supported categories (must match admin page)
$categories = ['General', 'Electronics', 'Audio', 'Mobile Devices', 'Wearables'];

// ── Build query ───────────────────────────────────────────────────────────────
$where  = ["p.status = 'Active'"];
$params = [];

if ($search !== '') {
    $where[]           = "(p.name LIKE :search OR p.description LIKE :search2)";
    $params['search']  = "%{$search}%";
    $params['search2'] = "%{$search}%";
}

if ($category !== '' && in_array($category, $categories, true)) {
    $where[]             = "p.category = :category";
    $params['category']  = $category;
}

$whereClause = implode(' AND ', $where);

// Total count for pagination
$countStmt = $database->prepare("SELECT COUNT(*) FROM products p WHERE {$whereClause}");
$countStmt->execute($params);
$totalProducts = (int) $countStmt->fetchColumn();
$totalPages    = (int) ceil($totalProducts / $perPage);

// Fetch products for this page
$productStmt = $database->prepare(
    "SELECT p.* FROM products p WHERE {$whereClause}
     ORDER BY p.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$productStmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$productStmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
foreach ($params as $k => $v) {
    $productStmt->bindValue(':' . $k, $v);
}
$productStmt->execute();
$products = $productStmt->fetchAll();

// Flash messages
$flashMessages = consume_flash_messages();

// Cart count for header
$cartCount = 0;
try {
    $cStmt = $database->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE customer_id = :cid");
    $cStmt->execute(['cid' => (int) $_SESSION['customer_id']]);
    $cartCount = (int) $cStmt->fetchColumn();
} catch (\Throwable) {}

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">Browse Products</h2>
            <p class="text-muted mb-0">
                <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> found
                <?= ($search !== '' || $category !== '') ? ' — <a href="shop.php" class="text-link-green">Clear filters</a>' : '' ?>
            </p>
        </div>
        <a href="cart.php" class="btn btn-primary-green position-relative">
            <i class="bi bi-cart3 me-1"></i> My Cart
            <?php if ($cartCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                      style="background:#ef4444; font-size:0.65rem;">
                    <?= $cartCount > 99 ? '99+' : $cartCount ?>
                </span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Flash Messages -->
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi <?= $msg['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- Search + Filter Row -->
    <div class="card border-0 shadow-sm p-4 mb-4">
        <form action="shop.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary" for="search-input">
                    <i class="bi bi-search me-1"></i> Search Products
                </label>
                <input id="search-input" type="text" name="search" class="form-control"
                       placeholder="Search by name or description…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold text-secondary" for="category-select">
                    <i class="bi bi-tag me-1"></i> Category
                </label>
                <select id="category-select" name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                            <?= e($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary-green">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Product Grid -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <div style="font-size:4rem; margin-bottom:16px;">🛒</div>
            <h4 class="fw-bold text-dark mb-2">No Products Found</h4>
            <p class="text-muted">
                <?= ($search !== '' || $category !== '') ? 'Try adjusting your search or filter.' : 'No products are currently available. Check back soon!' ?>
            </p>
            <?php if ($search !== '' || $category !== ''): ?>
                <a href="shop.php" class="btn btn-outline-green mt-2">
                    <i class="bi bi-arrow-left me-1"></i> View All Products
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <?php
                    $stock     = (int) $product['stock_quantity'];
                    $isOutOf   = $stock <= 0;
                    $isLow     = !$isOutOf && $stock <= 5;
                    $imgSrc    = (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path']))
                                    ? '../' . e($product['image_path'])
                                    : null;
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="product-card">
                        <!-- Product Image -->
                        <div class="product-img">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= e($product['name']) ?>"
                                     style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <i class="bi bi-box-seam"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Product Body -->
                        <div class="product-body">
                            <!-- Category Badge -->
                            <span class="badge rounded-pill mb-2"
                                  style="background:rgba(16,185,129,0.12); color:var(--brand-primary); font-size:0.7rem; font-weight:600; letter-spacing:0.4px;">
                                <?= e($product['category'] ?? 'General') ?>
                            </span>

                            <div class="product-name"><?= e($product['name']) ?></div>
                            <div class="product-sku">SKU: <?= e($product['sku']) ?></div>

                            <p class="text-muted mb-3" style="font-size:0.82rem; line-height:1.5; flex-grow:1;">
                                <?= e(mb_strimwidth($product['description'] ?? 'No description available.', 0, 90, '…')) ?>
                            </p>

                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="product-price"><?= money($product['price']) ?></div>
                                <div class="product-stock">
                                    <?php if ($isOutOf): ?>
                                        <span class="status-badge status-cancelled">
                                            <i class="bi bi-x-circle-fill"></i> Out of Stock
                                        </span>
                                    <?php elseif ($isLow): ?>
                                        <span class="status-badge status-out">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Only <?= $stock ?> left
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-delivered">
                                            <i class="bi bi-check-circle-fill"></i> In Stock
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Add to Cart Form -->
                            <form action="customer_action.php" method="POST" class="add-cart-form">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action"     value="cart_add">
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                                <?php if (!$isOutOf): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <label class="text-muted" style="font-size:0.8rem; white-space:nowrap;">Qty:</label>
                                        <div class="qty-control d-flex align-items-center border rounded-pill overflow-hidden" style="height:34px;">
                                            <button type="button" class="btn btn-sm border-0 qty-dec px-3"
                                                    style="height:100%; background:var(--surface-2);">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" name="quantity"
                                                   class="form-control border-0 text-center p-0 qty-input"
                                                   style="width:40px; font-size:0.85rem; font-weight:600;"
                                                   value="1" min="1" max="<?= $stock ?>">
                                            <button type="button" class="btn btn-sm border-0 qty-inc px-3"
                                                    style="height:100%; background:var(--surface-2);">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn-cart <?= $isOutOf ? 'disabled' : '' ?>"
                                        <?= $isOutOf ? 'disabled' : '' ?>>
                                    <i class="bi <?= $isOutOf ? 'bi-slash-circle' : 'bi-cart-plus' ?> me-1"></i>
                                    <?= $isOutOf ? 'Out of Stock' : 'Add to Cart' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-5 d-flex justify-content-center" aria-label="Product pagination">
                <ul class="pagination gap-1">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link rounded-2" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link rounded-2" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link rounded-2" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <p class="text-center text-muted mt-2" style="font-size:0.8rem;">
                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalProducts) ?> of <?= $totalProducts ?> products
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$pageScripts = <<<'JS'
<script>
document.querySelectorAll('.add-cart-form').forEach(form => {
    const qtyInput = form.querySelector('.qty-input');
    if (!qtyInput) return;
    const max = parseInt(qtyInput.max) || 99;

    form.querySelector('.qty-inc')?.addEventListener('click', () => {
        const v = parseInt(qtyInput.value) || 1;
        if (v < max) qtyInput.value = v + 1;
    });
    form.querySelector('.qty-dec')?.addEventListener('click', () => {
        const v = parseInt(qtyInput.value) || 1;
        if (v > 1) qtyInput.value = v - 1;
    });
});
</script>
JS;
require_once __DIR__ . '/components/footer.php';
?>