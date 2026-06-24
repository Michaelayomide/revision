<?php
$siteName = app_setting('website_name', 'AdminHub');
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$navItems = [
    ['href' => 'index.php', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
    ['href' => 'user.php', 'label' => 'Users', 'icon' => 'bi-people'],
    ['href' => 'products.php', 'label' => 'Products', 'icon' => 'bi-box-seam'],
    ['href' => 'stock.php', 'label' => 'Stock', 'icon' => 'bi-boxes'],
    ['href' => 'orders.php', 'label' => 'Orders', 'icon' => 'bi-bag-check'],
    ['href' => 'reports.php', 'label' => 'Reports', 'icon' => 'bi-graph-up'],
    ['href' => 'settings.php', 'label' => 'Settings', 'icon' => 'bi-gear'],
];
?>
<div class="sidebar d-none d-lg-block">
    <div class="nav flex-column pt-3">
        <?php foreach ($navItems as $item): ?>
            <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
            <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $activeClass; ?>">
                <i class="bi <?php echo e($item['icon']); ?> me-2"></i> <?php echo e($item['label']); ?>
            </a>
        <?php endforeach; ?>
        <hr class="text-white-50 mx-3">
        <a href="logout.php" class="nav-link text-danger fw-semibold">
            <i class="bi bi-box-arrow-right me-2"></i> Log Out
        </a>
    </div>
</div>

<div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#1e2937; color:white; width:270px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title fw-bold"><?php echo e($siteName); ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="nav flex-column">
            <?php foreach ($navItems as $item): ?>
                <?php $activeClass = $currentPage === $item['href'] ? ' active' : ''; ?>
                <a href="<?php echo e($item['href']); ?>" class="nav-link px-4 py-3<?php echo $activeClass; ?>">
                    <i class="bi <?php echo e($item['icon']); ?> me-3"></i> <?php echo e($item['label']); ?>
                </a>
            <?php endforeach; ?>
            <a href="logout.php" class="nav-link text-danger fw-semibold px-4 py-3">
                <i class="bi bi-box-arrow-right me-3"></i> Log Out
            </a>
        </div>
    </div>
</div>
