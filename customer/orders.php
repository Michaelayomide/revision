<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'My Orders';
$customerId = (int) $_SESSION['customer_id'];

// Fetch all orders for this customer, most recent first
$stmt = $database->prepare(
    "SELECT * FROM orders
     WHERE customer_id = :cid
     ORDER BY created_at DESC"
);
$stmt->execute(['cid' => $customerId]);
$orders = $stmt->fetchAll();

// Fetch all order items for these orders in one query (if any orders exist)
$orderItems = [];
if (!empty($orders)) {
    $orderIds    = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStmt = $database->prepare(
        "SELECT oi.*, p.image_path
         FROM order_items oi
         LEFT JOIN products p ON oi.product_id = p.id
         WHERE oi.order_id IN ({$placeholders})
         ORDER BY oi.id ASC"
    );
    $itemStmt->execute($orderIds);
    foreach ($itemStmt->fetchAll() as $row) {
        $orderItems[$row['order_id']][] = $row;
    }
}

$flashMessages = consume_flash_messages();

// Status → CSS class + icon map
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'Pending'         => 'status-pending',
        'Confirmed'       => 'status-confirmed',
        'Processing'      => 'status-processing',
        'Shipped',
        'Out for Delivery'=> 'status-shipped',
        'Delivered'       => 'status-delivered',
        'Cancelled'       => 'status-cancelled',
        default           => 'status-confirmed',
    };
}

function statusIcon(string $status): string
{
    return match ($status) {
        'Pending'         => 'bi-clock',
        'Confirmed'       => 'bi-check-circle',
        'Processing'      => 'bi-gear',
        'Shipped'         => 'bi-truck',
        'Out for Delivery'=> 'bi-box-arrow-in-right',
        'Delivered'       => 'bi-check-circle-fill',
        'Cancelled'       => 'bi-x-circle',
        default           => 'bi-circle',
    };
}

// Timeline steps for the order progress
$timelineSteps = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Delivered'];

function timelineStepState(string $currentStatus, string $step): string
{
    if ($currentStatus === 'Cancelled') return 'cancelled';
    $steps = ['Pending', 'Confirmed', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered'];
    $curr  = array_search($currentStatus, $steps, true);
    $s     = array_search($step, $steps, true);
    if ($s === false) return '';
    if ($s < $curr)  return 'done';
    if ($s === $curr) return 'active';
    return '';
}

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-1">My Orders</h2>
            <p class="text-muted mb-0">
                <?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?> total
            </p>
        </div>
        <a href="shop.php" class="btn btn-outline-green">
            <i class="bi bi-shop me-1"></i> Shop More
        </a>
    </div>

    <!-- Flash Messages -->
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <?= e($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <div style="font-size:4rem; margin-bottom:20px;">📦</div>
            <h4 class="fw-bold mb-2">No Orders Yet</h4>
            <p class="text-muted mb-4">You haven't placed any orders yet. Start shopping to see your orders here!</p>
            <a href="shop.php" class="btn btn-primary-green btn-lg">
                <i class="bi bi-shop me-2"></i> Start Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-4">
            <?php foreach ($orders as $ord): ?>
                <?php
                    $items      = $orderItems[$ord['id']] ?? [];
                    $itemCount  = count($items);
                    $statusClass = statusBadgeClass($ord['order_status']);
                    $statusIco   = statusIcon($ord['order_status']);
                    $isCancelled = $ord['order_status'] === 'Cancelled';
                ?>
                <div class="stat-card">

                    <!-- Order Header -->
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-4">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold fs-6" style="color:var(--brand-primary);">
                                    <?= e($ord['txn_id'] ?? '#' . $ord['id']) ?>
                                </span>
                                <span class="status-badge <?= $statusClass ?>">
                                    <i class="bi <?= $statusIco ?>"></i>
                                    <?= e($ord['order_status']) ?>
                                </span>
                            </div>
                            <div class="text-muted" style="font-size:0.82rem;">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('M j, Y — g:i A', strtotime($ord['created_at'])) ?>
                                &nbsp;·&nbsp;
                                <i class="bi bi-credit-card me-1"></i>
                                <?= e($ord['payment_method'] ?? 'N/A') ?>
                                &nbsp;·&nbsp;
                                <?= $itemCount ?> item<?= $itemCount !== 1 ? 's' : '' ?>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-5 text-success"><?= money($ord['total_amount']) ?></div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                Shipping: <?= e($ord['shipping_status'] ?? 'Processing') ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Timeline (not shown for cancelled) -->
                    <?php if (!$isCancelled): ?>
                        <div class="order-timeline mb-4">
                            <?php foreach ($timelineSteps as $step): ?>
                                <?php $state = timelineStepState($ord['order_status'], $step); ?>
                                <div class="timeline-step <?= $state ?>">
                                    <div class="timeline-dot">
                                        <?php if ($state === 'done'): ?>
                                            <i class="bi bi-check" style="font-size:0.8rem;"></i>
                                        <?php elseif ($state === 'active'): ?>
                                            <i class="bi bi-circle-fill" style="font-size:0.45rem;"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="timeline-label"><?= e($step) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert border-0 mb-4"
                             style="background:rgba(239,68,68,0.08); border-left:3px solid #ef4444!important; border-radius:10px;">
                            <i class="bi bi-x-circle text-danger me-2"></i>
                            <span class="text-danger fw-semibold">Order Cancelled</span>
                            <span class="text-muted ms-1" style="font-size:0.85rem;">— This order has been cancelled.</span>
                        </div>
                    <?php endif; ?>

                    <!-- Shipping Address -->
                    <?php if (!empty($ord['shipping_address'])): ?>
                        <div class="mb-4 p-3 rounded-3" style="background:var(--surface-2); border:1px solid var(--border);">
                            <div class="fw-semibold mb-1" style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.6px; color:var(--text-muted);">
                                <i class="bi bi-geo-alt me-1"></i> Shipping Address
                            </div>
                            <div style="font-size:0.88rem;"><?= e($ord['shipping_address']) ?></div>
                            <?php if (!empty($ord['shipping_phone'])): ?>
                                <div class="text-muted mt-1" style="font-size:0.82rem;">
                                    <i class="bi bi-telephone me-1"></i><?= e($ord['shipping_phone']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Items Accordion -->
                    <?php if (!empty($items)): ?>
                        <div>
                            <button class="btn btn-sm fw-semibold d-flex align-items-center gap-2 mb-3"
                                    style="background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:6px 14px; font-size:0.82rem;"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#items-<?= $ord['id'] ?>"
                                    aria-expanded="false">
                                <i class="bi bi-box-seam me-1"></i>
                                View <?= $itemCount ?> Item<?= $itemCount !== 1 ? 's' : '' ?>
                                <i class="bi bi-chevron-down toggle-icon" style="transition:transform 0.2s;"></i>
                            </button>
                            <div class="collapse" id="items-<?= $ord['id'] ?>">
                                <div class="rounded-3 overflow-hidden" style="border:1px solid var(--border);">
                                    <?php foreach ($items as $idx => $item): ?>
                                        <?php
                                            $imgSrc = (!empty($item['image_path']) && file_exists(__DIR__ . '/../' . $item['image_path']))
                                                         ? '../' . e($item['image_path'])
                                                         : null;
                                        ?>
                                        <div class="d-flex align-items-center gap-3 p-3"
                                             style="<?= $idx > 0 ? 'border-top:1px solid var(--border);' : '' ?>">
                                            <!-- Product image -->
                                            <div style="width:52px; height:52px; border-radius:10px; overflow:hidden; flex-shrink:0; background:var(--brand-light);">
                                                <?php if ($imgSrc): ?>
                                                    <img src="<?= $imgSrc ?>" alt="<?= e($item['product_name']) ?>"
                                                         style="width:100%; height:100%; object-fit:cover;">
                                                <?php else: ?>
                                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center"
                                                         style="color:var(--brand-primary); font-size:1.3rem;">
                                                        <i class="bi bi-box-seam"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <!-- Name + qty -->
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-semibold text-truncate" style="font-size:0.9rem;">
                                                    <?= e($item['product_name']) ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.78rem;">
                                                    Qty: <?= (int) $item['quantity'] ?> &nbsp;×&nbsp; <?= money($item['unit_price']) ?>
                                                </div>
                                            </div>
                                            <!-- Line total -->
                                            <div class="fw-bold text-success" style="white-space:nowrap;">
                                                <?= money((float) $item['unit_price'] * (int) $item['quantity']) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tracking URL -->
                    <?php if (!empty($ord['tracking_url'])): ?>
                        <div class="mt-3">
                            <a href="<?= e($ord['tracking_url']) ?>" target="_blank" rel="noopener"
                               class="btn btn-sm btn-outline-green">
                                <i class="bi bi-truck me-1"></i> Track Shipment
                            </a>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
$pageScripts = <<<'JS'
<script>
// Rotate chevron icon on accordion toggle
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(btn => {
    const target = document.querySelector(btn.getAttribute('data-bs-target'));
    if (!target) return;
    target.addEventListener('shown.bs.collapse',  () => btn.querySelector('.toggle-icon').style.transform = 'rotate(180deg)');
    target.addEventListener('hidden.bs.collapse', () => btn.querySelector('.toggle-icon').style.transform = 'rotate(0)');
});
</script>
JS;
require_once __DIR__ . '/components/footer.php';
?>