<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Notifications';
$adminId   = (int) ($_SESSION['admin_id'] ?? 0);

// Mark all as read via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $action = $_POST['action'] ?? '';
        if ($action === 'mark_all_read') {
            $database->prepare(
                "UPDATE notifications SET is_read = 1
                 WHERE recipient_type = 'admin' AND recipient_id = :id"
            )->execute(['id' => $adminId]);
            flash('success', 'All notifications marked as read.');
        } elseif ($action === 'mark_read') {
            $nid = (int) ($_POST['notification_id'] ?? 0);
            $database->prepare(
                "UPDATE notifications SET is_read = 1
                 WHERE id = :nid AND recipient_type = 'admin' AND recipient_id = :aid"
            )->execute(['nid' => $nid, 'aid' => $adminId]);
        }
    }
    redirect_to('notifications.php');
}

// Load notifications
$notifications = [];
$loadError     = '';

try {
    $stmt = $database->prepare(
        "SELECT id, type, message, extra_data, is_read, created_at
         FROM notifications
         WHERE recipient_type = 'admin' AND recipient_id = :id
         ORDER BY created_at DESC
         LIMIT 100"
    );
    $stmt->execute(['id' => $adminId]);
    $notifications = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Notifications could not be loaded. Ensure the migration has been applied.';
}

$flashMessages = consume_flash_messages();

$typeIcon = fn(string $t): string => match (true) {
    str_starts_with($t, 'new_order')    => 'bi-bag-plus-fill text-primary',
    str_starts_with($t, 'order_')       => 'bi-bag-check-fill text-success',
    str_starts_with($t, 'low_stock')    => 'bi-exclamation-triangle-fill text-warning',
    str_starts_with($t, 'payment')      => 'bi-credit-card-fill text-success',
    str_starts_with($t, 'support')      => 'bi-headset text-info',
    default                             => 'bi-bell-fill text-secondary',
};

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary mb-0">Notifications</h1>
        <?php $unread = array_filter($notifications, fn($n) => !(bool)$n['is_read']); ?>
        <?php if (!empty($unread)): ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-check2-all me-1"></i>Mark all as read
                </button>
            </form>
        <?php endif; ?>
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

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?php if ($notifications): ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($notifications as $notif): ?>
                        <?php $isUnread = !(bool) $notif['is_read']; ?>
                        <li class="list-group-item d-flex align-items-start gap-3 py-3 px-4<?php echo $isUnread ? ' bg-light' : ''; ?>">
                            <div class="mt-1">
                                <i class="bi <?php echo e($typeIcon($notif['type'])); ?> fs-5"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start">
                                    <p class="mb-1 fw-<?php echo $isUnread ? 'semibold' : 'normal'; ?>">
                                        <?php echo e($notif['message']); ?>
                                    </p>
                                    <?php if ($isUnread): ?>
                                        <form method="POST" class="ms-2 flex-shrink-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo e($notif['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-link p-0 text-muted" title="Mark as read">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><?php echo e(time_ago($notif['created_at'])); ?></small>
                                <?php if ($isUnread): ?>
                                    <span class="badge bg-primary ms-2" style="font-size:0.6rem;">New</span>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
                    No notifications yet.
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
