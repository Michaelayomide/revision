<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'Checkout';
$customerId = (int) $_SESSION['customer_id'];

// Redirect if cart is empty
$stmt = $database->prepare(
    "SELECT ci.id, ci.quantity, ci.product_id,
            p.name, p.price, p.stock_quantity, p.image_path, p.category
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.id
     WHERE ci.customer_id = :cid"
);
$stmt->execute(['cid' => $customerId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    redirect_to('cart.php');
}

// Compute totals
$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += (float) $item['price'] * (int) $item['quantity'];
}
$shipping = 15.00;
$total    = $subtotal + $shipping;

// Fetch customer profile (best-effort — some columns may be null)
$custStmt = $database->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$custStmt->execute(['id' => $customerId]);
$customer = $custStmt->fetch();

// Pre-build address string from profile columns (if they exist)
$prefillAddress = '';
$parts = array_filter([
    $customer['residential_address'] ?? '',
    $customer['city'] ?? '',
    $customer['state_province'] ?? '',
    $customer['country'] ?? '',
]);
$prefillAddress = implode(', ', $parts);

// Generate one-time order idempotency token (prevents duplicate on refresh)
if (empty($_SESSION['order_token'])) {
    $_SESSION['order_token'] = bin2hex(random_bytes(16));
}
$orderToken = $_SESSION['order_token'];

$flashMessages = consume_flash_messages();

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <!-- Page Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="font-size:0.82rem;">
                <li class="breadcrumb-item"><a href="shop.php" class="text-link-green">Shop</a></li>
                <li class="breadcrumb-item"><a href="cart.php" class="text-link-green">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">Secure Checkout</h2>
    </div>

    <!-- Flash Messages -->
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi <?= $msg['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
            <?= e($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <form id="checkout-form" method="POST" action="customer_action.php" novalidate>
        <input type="hidden" name="csrf_token"  value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action"       value="place_order">
        <input type="hidden" name="order_token"  value="<?= e($orderToken) ?>">

        <div class="row g-4 align-items-start">

            <!-- Left: Shipping + Payment -->
            <div class="col-lg-7">

                <!-- Recipient Info -->
                <div class="stat-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="stat-icon" style="background:rgba(16,185,129,0.12);">
                            <i class="bi bi-person-fill" style="color:var(--brand-primary);"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Recipient Information</h5>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control bg-light"
                               value="<?= e($_SESSION['customer_name'] ?? '') ?>" readonly>
                        <div class="form-text">Name from your account profile.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="shipping-phone">
                            Phone Number <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="tel" id="shipping-phone" name="shipping_phone"
                                   class="form-control" required
                                   placeholder="e.g. +234 801 234 5678"
                                   value="<?= e($customer['phone_number'] ?? '') ?>">
                        </div>
                        <div class="invalid-feedback">Please enter a valid phone number.</div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="stat-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="stat-icon" style="background:rgba(59,130,246,0.12);">
                            <i class="bi bi-geo-alt-fill" style="color:#3b82f6;"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Shipping Address</h5>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="shipping-address">
                            Full Delivery Address <span class="text-danger">*</span>
                        </label>
                        <textarea id="shipping-address" name="shipping_address"
                                  class="form-control" rows="3" required
                                  placeholder="Enter your full delivery address including street, city, state, and country…"><?= e($prefillAddress) ?></textarea>
                        <div class="invalid-feedback">Please provide a delivery address.</div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="stat-card mb-4">
                    <div class="d-flex align-items-center gap-2 mb-4">
                        <div class="stat-icon" style="background:rgba(245,158,11,0.12);">
                            <i class="bi bi-credit-card-fill" style="color:#f59e0b;"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Payment Method</h5>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="payment-option" style="display:block; cursor:pointer;">
                                <input type="radio" name="payment_method" value="Cash on Delivery"
                                       class="visually-hidden payment-radio" checked>
                                <div class="p-3 border rounded-3 text-center payment-card"
                                     style="transition:all 0.2s;">
                                    <i class="bi bi-cash-coin fs-3 mb-2" style="color:#f59e0b;"></i>
                                    <div class="fw-semibold" style="font-size:0.88rem;">Cash on Delivery</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Pay when you receive</div>
                                </div>
                            </label>
                        </div>
                        <div class="col-sm-6">
                            <label class="payment-option" style="display:block; cursor:pointer;">
                                <input type="radio" name="payment_method" value="Bank Transfer"
                                       class="visually-hidden payment-radio">
                                <div class="p-3 border rounded-3 text-center payment-card"
                                     style="transition:all 0.2s;">
                                    <i class="bi bi-bank fs-3 mb-2" style="color:#3b82f6;"></i>
                                    <div class="fw-semibold" style="font-size:0.88rem;">Bank Transfer</div>
                                    <div class="text-muted" style="font-size:0.75rem;">Direct bank payment</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Order Summary -->
            <div class="col-lg-5">
                <div class="stat-card" style="position:sticky; top:calc(var(--navbar-height) + 20px);">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-bag-check me-2" style="color:var(--brand-primary);"></i>
                        Order Review
                    </h5>

                    <!-- Cart Items List -->
                    <div class="mb-4" style="max-height:280px; overflow-y:auto;">
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                                $imgSrc = (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . $item['image_path']))
                                             ? '../' . e($item['image_path'])
                                             : null;
                            ?>
                            <div class="d-flex align-items-center gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--border);">
                                <!-- Mini image -->
                                <div style="width:44px; height:44px; border-radius:8px; overflow:hidden; flex-shrink:0; background:var(--brand-light);">
                                    <?php if ($imgSrc): ?>
                                        <img src="<?= $imgSrc ?>" alt="<?= e($item['name']) ?>"
                                             style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                             style="color:var(--brand-primary);">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" style="font-size:0.88rem;">
                                        <?= e($item['name']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.78rem;">
                                        Qty: <?= (int) $item['quantity'] ?> × <?= money($item['price']) ?>
                                    </div>
                                </div>
                                <div class="fw-bold text-success" style="font-size:0.9rem; white-space:nowrap;">
                                    <?= money((float) $item['price'] * (int) $item['quantity']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Totals -->
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-semibold"><?= money($subtotal) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted"><i class="bi bi-truck me-1"></i>Shipping</span>
                        <span class="fw-semibold"><?= money($shipping) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4 pt-3 fw-bold fs-5" style="border-top:2px solid var(--border);">
                        <span>Total</span>
                        <span class="text-success"><?= money($total) ?></span>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="place-order-btn" class="btn btn-primary-green w-100 py-3 fw-bold" style="font-size:1.05rem;">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        Place Order — <?= money($total) ?>
                    </button>

                    <div class="mt-3 text-center text-muted" style="font-size:0.73rem; line-height:1.7;">
                        <i class="bi bi-shield-check text-success me-1"></i>
                        Your order is protected by our secure checkout process.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php
$pageScripts = <<<'JS'
<script>
// Payment card visual selection
document.querySelectorAll('.payment-radio').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('.payment-card').forEach(card => {
            card.style.borderColor = '';
            card.style.background  = '';
        });
        if (radio.checked) {
            const card = radio.closest('.payment-option').querySelector('.payment-card');
            card.style.borderColor = 'var(--brand-primary)';
            card.style.background  = 'rgba(16,185,129,0.06)';
        }
    });
    // Initialise active state
    if (radio.checked) {
        const card = radio.closest('.payment-option').querySelector('.payment-card');
        card.style.borderColor = 'var(--brand-primary)';
        card.style.background  = 'rgba(16,185,129,0.06)';
    }
});

// Form validation + one-shot submit guard
const form = document.getElementById('checkout-form');
const btn  = document.getElementById('place-order-btn');
let submitting = false;

form.addEventListener('submit', function(e) {
    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    if (submitting) {
        e.preventDefault();
        return;
    }
    submitting = true;
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Placing order…';
});
</script>
JS;
require_once __DIR__ . '/components/footer.php';
?>