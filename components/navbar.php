<?php
$siteName = app_setting('website_name', 'AdminHub');
$notificationsEnabled = app_setting('notifications_enabled', '1') === '1';
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
            <input class="form-control rounded-pill d-none d-md-block app-search" type="search" placeholder="Search..." aria-label="Search">
            <?php if ($notificationsEnabled): ?>
                <div class="d-flex gap-3 text-white fs-5">
                    <i class="bi bi-bell" title="Notifications"></i>
                    <i class="bi bi-envelope" title="Messages"></i>
                </div>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-2 text-white">
                <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Avatar">
                <div class="d-none d-sm-block">
                    <small class="fw-bold"><?php echo e($admin_display_name ?? 'Admin User'); ?></small><br>
                    <small class="text-success">Online</small>
                </div>
            </div>
        </div>
    </div>
</nav>
