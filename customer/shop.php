<?php
// customer/shop.php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $database->prepare(
        "SELECT * FROM products WHERE status = 'Active' AND (name LIKE :search OR description LIKE :search2)"
    );
    $stmt->execute(['search' => "%{$search}%", 'search2' => "%{$search}%"]);
} else {
    $stmt = $database->prepare("SELECT * FROM products WHERE status = 'Active'");
    $stmt->execute();
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Storefront - Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .product-card { transition: transform 0.2s, box-shadow 0.2s; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
        .product-img { height: 200px; object-fit: cover; background-color: #f8f9fa; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold text-uppercase" href="shop.php">Storefront</a>
            <div class="d-flex align-items-center">
                <a href="cart.php" class="btn btn-outline-light me-2">View Cart</a>
                <a href="orders.php" class="btn btn-outline-light me-2">My Orders</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if (function_exists('render_flash_messages')) { render_flash_messages(); } ?>

        <div class="row mb-4">
            <div class="col-md-6 mx-auto">
                <form action="shop.php" method="GET" class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            </div>
        </div>

        <h2 class="mb-4 fw-bold text-secondary">Available Products</h2>
        <div class="row g-4">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <p class="text-muted fs-5">No products found matching your description.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4">
                        <div class="card h-100 product-card">
                            <?php 
                                $imgSrc = (!empty($product['image_path']) && file_exists(__DIR__ . '/../' . $product['image_path'])) 
                                    ? '../' . htmlspecialchars($product['image_path']) 
                                    : 'https://via.placeholder.com/300x200?text=No+Image+Available';
                            ?>
                            <img src="<?= $imgSrc ?>" class="card-img-top product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-2 align-self-start"><?= htmlspecialchars($product['category'] ?? 'General') ?></span>
                                <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text text-muted text-truncateSmall flex-grow-1">
                                    <?= htmlspecialchars($product['description'] ?? 'No description available.') ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="fs-4 fw-bold text-success">$<?= number_format((float)$product['price'], 2) ?></span>
                                    <span class="badge bg-light text-dark border">Stocks: <?= (int)$product['stock_quantity'] ?></span>
                                </div>
                                <form action="customer_action.php" method="POST" class="mt-3">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="action" value="cart_add">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Qty</span>
                                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= (int)$product['stock_quantity'] ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100" <?= ((int)$product['stock_quantity'] <= 0) ? 'disabled' : '' ?>>
                                        <?= ((int)$product['stock_quantity'] <= 0) ? 'Out of Stock' : 'Add to Cart' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>