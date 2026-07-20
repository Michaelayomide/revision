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
<div class="sidebar d-none d-lg-block" role="navigation" aria-label="Main navigation">
    <div class="sidebar-scroll-engine">
        <div class="sidebar-nav-label">Main Menu</div>
        <div class="nav flex-column">
            <?php foreach ($visibleItems as $item): ?>
                <?php $isActive = $currentPage === $item['href']; ?>
                <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $isActive ? ' active' : ''; ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                    <i class="bi <?php echo e($item['icon']); ?>"></i>
                    <span class="flex-grow-1"><?php echo e($item['label']); ?></span>
                    <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sidebar-footer-pinned">
        <a href="logout.php" class="nav-link fw-semibold">
            <i class="bi bi-box-arrow-right"></i> Log Out
        </a>
    </div>
</div>

<div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#0b0f19; color:#cbd5e1; width: var(--sidebar-width);" aria-label="Mobile navigation">
    <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <h5 class="offcanvas-title fw-bold text-white d-flex align-items-center gap-2">
            <span class="brand-logo-icon"><i class="bi bi-grid-1x2-fill"></i></span>
            <?php echo e($siteName); ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body p-0">
        <div class="sidebar-scroll-engine pt-2">
            <div class="sidebar-nav-label">Main Menu</div>
            <div class="nav flex-column">
                <?php foreach ($visibleItems as $item): ?>
                    <?php $isActive = $currentPage === $item['href']; ?>
                    <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $isActive ? ' active' : ''; ?>"<?php echo $isActive ? ' aria-current="page"' : ''; ?>>
                        <i class="bi <?php echo e($item['icon']); ?>"></i>
                        <span class="flex-grow-1"><?php echo e($item['label']); ?></span>
                        <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sidebar-footer-pinned">
            <a href="logout.php" class="nav-link fw-semibold">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </a>
        </div>
    </div>
</div>