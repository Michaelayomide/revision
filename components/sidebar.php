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
<style>
    /* ==========================================================================
   PRODUCTION CROSS-PLATFORM SIDEBAR CONTROLS (DESKTOP & LAPTOP)
   ========================================================================== */
@media (min-width: 992px) {
    .sidebar {
        background: var(--dark);
        color: white;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        width: 270px;
        z-index: 1000;
        padding-top: 75px; /* Alignment gap spacing layer for top headers */
        box-shadow: 3px 0 15px rgba(0,0,0,0.2);
        
        /* Flex Alignment Architecture */
        display: flex !important;
        flex-direction: column;
        overflow: hidden; /* Lock master viewport wrapper bounds */
    }

    /* Independent Scroll Frame Axis for Laptop Layouts */
    .sidebar-scroll-engine {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
    }
}

/* ==========================================================================
   MOBILE & TABLET VIEWPORT ISOLATION (OFFCANVAS ADAPTATION)
   ========================================================================== */
@media (max-width: 991.98px) {
    .offcanvas-body {
        display: flex !important;
        flex-direction: column;
        overflow: hidden !important; /* Disables total container broken scrolling */
        height: 100%;
    }

    /* Independent Scroll Frame Axis for Mobile Panels */
    .sidebar-scroll-engine {
        flex-grow: 1;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch; /* Native mobile inertial momentum velocity scroll */
    }
}

/* ==========================================================================
   SHARED ANIMATION & GRAPHICAL LINK UTILITIES (PRESERVED HOVER RENDERING)
   ========================================================================== */
.sidebar .nav-link,
#mobileSidebar .nav-link {
    color: #cbd5e1;
    padding: 15px 25px;
    font-weight: 500;
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    position: relative;
    text-decoration: none;
}

.sidebar .nav-link:hover,
.sidebar .nav-link.active,
#mobileSidebar .nav-link:hover,
#mobileSidebar .nav-link.active {
    background: #334155;
    color: white;
    border-left: 4px solid var(--primary);
    transform: translateX(8px);
}

/* Pinned Footer Layer Component Boundary Matrix */
.sidebar-footer-pinned {
    margin-top: auto;
    padding: 10px 0;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    background: rgba(0, 0, 0, 0.1);
}
</style>
<div class="sidebar d-none d-lg-block">
    <div class="sidebar-scroll-engine">
        <div class="nav flex-column">
            <?php foreach ($visibleItems as $item): ?>
                <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
                <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $activeClass; ?>">
                    <i class="bi <?php echo e($item['icon']); ?> me-3 fs-5"></i> 
                    <span class="flex-grow-1"><?php echo e($item['label']); ?></span>
                    <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                        <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="sidebar-footer-pinned">
        <a href="logout.php" class="nav-link text-danger fw-semibold">
            <i class="bi bi-box-arrow-right me-3 fs-5"></i> Log Out
        </a>
    </div>
</div>

<div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#1e2937; color:white; width:270px;">
    <div class="offcanvas-header border-bottom border-secondary border-opacity-25">
        <h5 class="offcanvas-title fw-bold text-white"><?php echo e($siteName); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    
    <div class="offcanvas-body p-0">
        <div class="sidebar-scroll-engine pt-2">
            <div class="nav flex-column">
                <?php foreach ($visibleItems as $item): ?>
                    <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
                    <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $activeClass; ?>">
                        <i class="bi <?php echo e($item['icon']); ?> me-3 fs-5"></i> 
                        <span class="flex-grow-1"><?php echo e($item['label']); ?></span>
                        <?php if ($item['href'] === 'notifications.php' && ($unread_notif_count ?? 0) > 0): ?>
                            <span class="badge rounded-pill bg-danger ms-auto" style="font-size:0.6rem;"><?php echo $unread_notif_count; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sidebar-footer-pinned">
            <a href="logout.php" class="nav-link text-danger fw-semibold">
                <i class="bi bi-box-arrow-right me-3 fs-5"></i> Log Out
            </a>
        </div>
    </div>
</div>