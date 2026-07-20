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
    'secondary_admin' => '#2563eb',
    'editor'          => '#ea580c',
    default           => '#6b7280',
};
$adminName   = $admin_display_name ?? 'Admin User';
$adminAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($adminName) . '&background=2563eb&color=fff';
?>
<nav class="navbar fixed-top" aria-label="Top navigation">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <button class="navbar-toggler me-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-label="Open navigation" aria-controls="mobileSidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="index.php">
                <span class="brand-logo-icon d-inline-flex d-lg-none"><i class="bi bi-grid-1x2-fill"></i></span>
                <span class="d-none d-lg-inline"><?php echo e($siteName); ?></span>
            </a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <form method="POST" onsubmit="event.preventDefault();" class="d-none d-md-block" role="search">
                <div class="position-relative">
                    <i class="bi bi-search navbar-search-icon"></i>
                    <input class="form-control app-search" type="search" name="navbar_search" placeholder="Search..." aria-label="Search">
                </div>
            </form>

            <div class="nav-divider d-none d-md-block"></div>

            <?php if ($notificationsEnabled): ?>
                <a href="notifications.php" class="nav-icon-btn" title="Notifications" id="notifBellLink" aria-label="Notifications">
                    <i class="bi bi-bell"></i>
                    <?php if (($unread_notif_count ?? 0) > 0): ?>
                        <span class="notif-badge"><?php echo $unread_notif_count > 99 ? '99+' : $unread_notif_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <div class="dropdown">
                <button class="nav-profile border-0" type="button" id="profileMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <img src="<?php echo e($adminAvatar); ?>" class="avatar" alt="<?php echo e($adminName); ?>">
                    <span class="d-none d-sm-flex flex-column text-start">
                        <span class="np-name"><?php echo e($adminName); ?></span>
                        <span class="np-role" style="color: <?php echo e($roleBadgeColor); ?>;"><?php echo e($roleLabel); ?></span>
                    </span>
                    <i class="bi bi-chevron-down d-none d-sm-inline small text-muted"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileMenu">
                    <li class="dropdown-header"><?php echo e($adminName); ?></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                    <li><a class="dropdown-item" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i> Log Out</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
