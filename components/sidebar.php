<?php
$siteName    = app_setting('website_name', 'AdminHub');
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$role        = current_admin_role();

// Build nav items based on role
$navItems = [
    ['href' => 'index.php',         'label' => 'Dashboard',  'icon' => 'bi-speedometer2', 'roles' => ['primary_admin','secondary_admin','editor']],
    ['href' => 'products.php',      'label' => 'Products',   'icon' => 'bi-box-seam',     'roles' => ['primary_admin','secondary_admin','editor']],
    ['href' => 'stock.php',         'label' => 'Stock',      'icon' => 'bi-boxes',         'roles' => ['primary_admin','secondary_admin','editor']],
    ['href' => 'orders.php',        'label' => 'Orders',     'icon' => 'bi-bag-check',     'roles' => ['primary_admin','secondary_admin','editor']],
    ['href' => 'customers.php',     'label' => 'Customers',  'icon' => 'bi-people-fill',   'roles' => ['primary_admin','secondary_admin']],
    ['href' => 'user.php',          'label' => 'Staff',      'icon' => 'bi-person-badge',  'roles' => ['primary_admin','secondary_admin']],
    ['href' => 'reports.php',       'label' => 'Reports',    'icon' => 'bi-graph-up',      'roles' => ['primary_admin']],
    ['href' => 'notifications.php', 'label' => 'Inbox',      'icon' => 'bi-bell',          'roles' => ['primary_admin','secondary_admin','editor']],
    ['href' => 'settings.php',      'label' => 'Settings',   'icon' => 'bi-gear',          'roles' => ['primary_admin','secondary_admin']],
];

$visibleItems = array_filter($navItems, fn($item) => in_array($role, $item['roles'], true));
?>
<div class="sidebar d-none d-lg-block">
    <div class="nav flex-column pt-3">
        <?php foreach ($visibleItems as $item): ?>
            <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
            <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $activeClass; ?>">
                <i class="bi <?php echo e($item['icon']); ?> me-2"></i> <?php echo e($item['label']); ?>
                <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                    <span class="badge rounded-pill ms-auto" style="background:#ef4444; font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        <hr class="text-white-50 mx-3">
        <a href="logout.php" class="nav-link text-danger fw-semibold">
            <i class="bi bi-box-arrow-right me-2"></i> Log Out
        </a>
    </div>
</div>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#1e2937; color:white; width:270px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold"><?php echo e($siteName); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="nav flex-column">
            <?php foreach ($visibleItems as $item): ?>
                <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
                <a href="<?php echo e($item['href']); ?>" class="nav-link px-4 py-3<?php echo $activeClass; ?>">
                    <i class="bi <?php echo e($item['icon']); ?> me-3"></i> <?php echo e($item['label']); ?>
                    <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                        <span class="badge rounded-pill ms-2" style="background:#ef4444; font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
            <a href="logout.php" class="nav-link text-danger fw-semibold px-4 py-3">
                <i class="bi bi-box-arrow-right me-3"></i> Log Out
            </a>
        </div>
    </div>
</div>
