<?php
$siteName             = app_setting('website_name', 'AdminHub');
$notificationsEnabled = app_setting('notifications_enabled', '1') === '1';
$currentRole          = current_admin_role();

$roleLabel = match ($currentRole) {
    'primary_admin'   => 'Primary Admin',
    'secondary_admin' => 'Secondary Admin',
    'editor'          => 'Editor',
    default           => 'Admin',
};
$roleBadgeColor = match ($currentRole) {
    'primary_admin'   => '#10b981',
    'secondary_admin' => '#6366f1',
    'editor'          => '#f59e0b',
    default           => '#6b7280',
};
?>
<nav class="navbar navbar-dark fixed-top py-3 shadow-sm">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler me-3 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Open navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand fw-bold fs-3" href="index.php"><?php echo e($siteName); ?></a>
        </div>

        <div class="d-flex align-items-center gap-3">
            <form method="POST" onsubmit="event.preventDefault();" class="d-none d-md-block">
                <div class="position-relative">
                    <input class="form-control rounded-pill app-search" type="search" name="navbar_search" placeholder="Search..." aria-label="Search">
                </div>
            </form>

            <?php if ($notificationsEnabled): ?>
                <div class="d-flex gap-3 text-white fs-5 align-items-center">
                    <!-- Notifications bell with badge -->
                    <a href="notifications.php" class="position-relative text-white text-decoration-none" title="Notifications" id="notifBellLink">
                        <i class="bi bi-bell"></i>
                        <?php if ($unread_notif_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill"
                                  style="background:#ef4444; font-size:0.6rem; min-width:18px; padding:3px 5px;">
                                <?php echo $unread_notif_count > 99 ? '99+' : $unread_notif_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>

            <div class="d-flex align-items-center gap-2 text-white">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin_display_name ?? 'Admin User'); ?>&background=6366f1&color=fff"
                     class="rounded-circle" alt="Avatar" width="40" height="40">
                <div class="d-none d-sm-block">
                    <small class="fw-bold"><?php echo e($admin_display_name ?? 'Admin User'); ?></small><br>
                    <small style="color: <?php echo e($roleBadgeColor); ?>; font-size: 0.7rem; font-weight: 600;">
                        <?php echo e($roleLabel); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</nav>
