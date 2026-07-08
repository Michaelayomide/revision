<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'My Cart';
$customerId = (int) $_SESSION['customer_id'];

// Fetch cart items with product details
$stmt = $database->prepare(
    "SELECT ci.id AS cart_item_id, ci.quantity,
            p.id AS product_id, p.name, p.description, p.price, p.stock_quantity, p.image_path, p.category
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.id
     WHERE ci.customer_id = :cid
     ORDER BY ci.added_at DESC"
);
$stmt->execute(['cid' => $customerId]);
$cartItems = $stmt->fetchAll();

// Compute totals
$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}
$shipping = $subtotal > 0 ? 15.00 : 0.0;
$total    = $subtotal + $shipping;

$flashMessages = consume_flash_messages();

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">Shopping Cart</h2>
            <p class="text-muted mb-0">
                <?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart
            </p>
        </div>
        <a href="shop.php" class="btn btn-outline-green">
            <i class="bi bi-arrow-left me-1"></i> Continue Shopping
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

    <?php if (empty($cartItems)): ?>
        <!-- Empty cart state -->
        <div class="text-center py-5">
            <div style="font-size:5rem; margin-bottom:20px;">🛒</div>
            <h4 class="fw-bold mb-2">Your cart is empty</h4>
            <p class="text-muted mb-4">Looks like you haven't added anything yet. Start exploring our products!</p>
            <a href="shop.php" class="btn btn-primary-green btn-lg">
                <i class="bi bi-shop me-2"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4 align-items-start">

            <!-- Cart Items -->
            <div class="col-lg-8">
                <?php foreach ($cartItems as $item): ?>
                    <?php
                        $stock   = (int) $item['stock_quantity'];
                        $qty     = (int) $item['quantity'];
                        $imgSrc  = (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . $item['image_path']))
                                      ? '../' . e($item['image_path'])
                                      : null;
                        $lineTotal = (float) $item['price'] * $qty;
                    ?>
                    <div class="cart-item">
                        <!-- Product Image / Icon -->
                        <div class="cart-item-icon flex-shrink-0" style="width:72px; height:72px; border-radius:12px; overflow:hidden;">
                            <?php if ($imgSrc): ?>
                                <img src="<?= $imgSrc ?>" alt="<?= e($item['name']) ?>"
                                     style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                     style="background:var(--brand-light); color:var(--brand-primary); font-size:1.6rem;">
                                    <i class="bi bi-box-seam"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div>
                                    <div class="fw-bold mb-1" style="font-size:0.95rem;"><?= e($item['name']) ?></div>
                                    <div class="text-muted mb-1" style="font-size:0.78rem;">
                                        <span class="badge rounded-pill" style="background:rgba(16,185,129,0.12); color:var(--brand-primary);">
                                            <?= e($item['category'] ?? 'General') ?>
                                        </span>
                                    </div>
                                    <div style="font-size:0.82rem; color:var(--text-muted);">
                                        <?= $stock <= 5 && $stock > 0 ? '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Only ' . $stock . ' left in stock' : '' ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-success fs-5"><?= money($lineTotal) ?></div>
                                    <div class="text-muted" style="font-size:0.78rem;"><?= money($item['price']) ?> each</div>
                                </div>
                            </div>

                            <!-- Quantity + Remove -->
                            <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
                                <!-- Update quantity -->
                                <form method="POST" action="customer_action.php" class="d-flex align-items-center gap-2">
                                    <input type="hidden" name="csrf_token"    value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action"        value="cart_update">
                                    <input type="hidden" name="cart_item_id"  value="<?= e($item['cart_item_id']) ?>">
                                    <div class="qty-control d-flex align-items-center border rounded-pill overflow-hidden"
                                         style="height:34px;">
                                        <button type="button" class="btn btn-sm border-0 qty-dec px-3"
                                                style="height:100%; background:var(--surface-2);">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" name="quantity"
                                               class="form-control border-0 text-center p-0 qty-input"
                                               style="width:44px; font-size:0.9rem; font-weight:600;"
                                               value="<?= $qty ?>" min="1" max="<?= $stock ?>">
                                        <button type="button" class="btn btn-sm border-0 qty-inc px-3"
                                                style="height:100%; background:var(--surface-2);">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-outline-green" style="font-size:0.8rem;">
                                        Update
                                    </button>
                                </form>

                                <!-- Remove -->
                                <form method="POST" action="customer_action.php">
                                    <input type="hidden" name="csrf_token"    value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action"        value="cart_remove">
                                    <input type="hidden" name="cart_item_id"  value="<?= e($item['cart_item_id']) ?>">
                                    <button type="submit" class="btn btn-sm"
                                            style="color:var(--danger); border:1px solid var(--danger); border-radius:8px; font-size:0.8rem; padding:5px 12px;"
                                            onclick="return confirm('Remove this item from cart?')">
                                        <i class="bi bi-trash me-1"></i> Remove
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="stat-card" style="position:sticky; top:calc(var(--navbar-height) + 20px);">
                    <h5 class="fw-bold mb-4">Order Summary</h5>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal (<?= count($cartItems) ?> items)</span>
                        <span class="fw-semibold"><?= money($subtotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">
                            <i class="bi bi-truck me-1"></i> Shipping
                        </span>
                        <span class="fw-semibold"><?= money($shipping) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 pt-3" style="border-top:2px solid var(--border);">
                        <span class="fw-bold fs-5">Total</span>
                        <span class="fw-bold fs-5 text-success"><?= money($total) ?></span>
                    </div>

                    <a href="checkout.php" class="btn btn-primary-green w-100 py-3 fw-bold" style="font-size:1rem;">
                        <i class="bi bi-lock-fill me-2"></i> Proceed to Checkout
                    </a>

                    <a href="shop.php" class="btn btn-outline-green w-100 mt-3 py-2" style="font-size:0.88rem;">
                        <i class="bi bi-shop me-1"></i> Continue Shopping
                    </a>

                    <div class="mt-4 text-center text-muted" style="font-size:0.75rem; line-height:1.6;">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Secure checkout · Flat $15 shipping fee
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$pageScripts = <<<'JS'
<script>
document.querySelectorAll('.qty-control').forEach(control => {
    const input = control.querySelector('.qty-input');
    if (!input) return;
    const max = parseInt(input.max) || 99;
    control.querySelector('.qty-inc')?.addEventListener('click', () => {
        const v = parseInt(input.value) || 1;
        if (v < max) input.value = v + 1;
    });
    control.querySelector('.qty-dec')?.addEventListener('click', () => {
        const v = parseInt(input.value) || 1;
        if (v > 1) input.value = v - 1;
    });
});
</script>
JS;
require_once __DIR__ . '/components/footer.php';
?>