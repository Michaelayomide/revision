<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'Notifications';
$customerId = (int) $_SESSION['customer_id'];

// Mark all unread as read when the page is opened
try {
    $database->prepare(
        "UPDATE notifications SET is_read = 1
         WHERE recipient_type = 'customer' AND recipient_id = :id AND is_read = 0"
    )->execute(['id' => $customerId]);
} catch (\Throwable) {}

// Fetch notifications (most recent first)
$stmt = $database->prepare(
    "SELECT * FROM notifications
     WHERE recipient_type = 'customer' AND recipient_id = :id
     ORDER BY created_at DESC
     LIMIT 60"
);
$stmt->execute(['id' => $customerId]);
$notifications = $stmt->fetchAll();

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">

    <!-- Page Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1">Notifications</h2>
            <p class="text-muted mb-0"><?= count($notifications) ?> notification<?= count($notifications) !== 1 ? 's' : '' ?></p>
        </div>
    </div>

    <div class="stat-card p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 px-4">
                <div style="font-size:3.5rem; margin-bottom:16px;">🔔</div>
                <h5 class="fw-bold mb-2">All caught up!</h5>
                <p class="text-muted mb-0">You have no notifications yet. They'll appear here when your orders are updated.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <?php
                    $type = $notif['type'] ?? 'general';
                    [$iconClass, $iconBg, $iconColor] = match (true) {
                        str_contains($type, 'order_placed')    => ['bi-bag-check-fill',    'rgba(16,185,129,0.12)',  'var(--brand-primary)'],
                        str_contains($type, 'order_confirmed') => ['bi-check-circle-fill', 'rgba(59,130,246,0.12)',  '#3b82f6'],
                        str_contains($type, 'order_processing')=> ['bi-gear-fill',         'rgba(139,92,246,0.12)', '#8b5cf6'],
                        str_contains($type, 'order_shipped')   => ['bi-truck',             'rgba(14,165,233,0.12)', '#0ea5e9'],
                        str_contains($type, 'order_delivered') => ['bi-check-circle-fill', 'rgba(16,185,129,0.12)',  'var(--brand-primary)'],
                        str_contains($type, 'order_cancelled') => ['bi-x-circle-fill',     'rgba(239,68,68,0.12)',  '#ef4444'],
                        default                                 => ['bi-bell-fill',         'rgba(100,116,139,0.12)','var(--text-secondary)'],
                    };
                    $isUnread = !(bool) $notif['is_read'];
                ?>
                <div class="notif-item <?= $isUnread ? 'unread' : '' ?>">
                    <div class="notif-icon" style="background:<?= $iconBg ?>; color:<?= $iconColor ?>;">
                        <i class="bi <?= $iconClass ?>"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold mb-1" style="font-size:0.9rem; line-height:1.4;">
                            <?= e($notif['message']) ?>
                        </div>
                        <div class="text-muted" style="font-size:0.75rem;">
                            <i class="bi bi-clock me-1"></i><?= time_ago($notif['created_at']) ?>
                            <?php if ($isUnread): ?>
                                <span class="ms-2 badge rounded-pill"
                                      style="background:var(--brand-primary); color:#fff; font-size:0.65rem;">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?>
