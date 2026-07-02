<?php
$siteName    = app_setting('website_name', 'AdminHub');
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$customerId  = (int) ($_SESSION['customer_id'] ?? 0);
$customerName = $_SESSION['customer_name'] ?? 'Customer';
$unreadCount = 0;
if ($customerId > 0) {
    try {
        $unreadCount = unread_customer_notification_count($database, $customerId);
    } catch (\Throwable $e) { $unreadCount = 0; }
}

$navItems = [
    ['href' => 'index.php',         'label' => 'Dashboard',      'icon' => 'bi-speedometer2'],
    ['href' => 'shop.php',          'label' => 'Shop',           'icon' => 'bi-shop'],
    ['href' => 'cart.php',          'label' => 'My Cart',        'icon' => 'bi-cart3'],
    ['href' => 'orders.php',        'label' => 'My Orders',      'icon' => 'bi-bag-check'],
    ['href' => 'notifications.php', 'label' => 'Notifications',  'icon' => 'bi-bell'],
    ['href' => 'profile.php',       'label' => 'My Profile',     'icon' => 'bi-person-circle'],
];
?>

<!-- Sidebar -->
<aside class="customer-sidebar" id="customerSidebar">
    <div class="sidebar-brand">
        <a href="index.php"><?php echo e($siteName); ?></a>
        <span>Customer</span>
    </div>

    <nav class="nav flex-column mt-2">
        <div class="nav-section-label">Menu</div>
        <?php foreach ($navItems as $item): ?>
            <?php $isActive = $currentPage === $item['href']; ?>
            <a href="<?php echo e($item['href']); ?>" class="nav-link<?php echo $isActive ? ' active' : ''; ?>">
                <span class="nav-icon"><i class="bi <?php echo e($item['icon']); ?>"></i></span>
                <?php echo e($item['label']); ?>
                <?php if ($item['href'] === 'notifications.php' && $unreadCount > 0): ?>
                    <span class="badge rounded-pill ms-auto" style="background:#ef4444; font-size:0.6rem; min-width:18px;">
                        <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                    </span>
                <?php endif; ?>
                <?php if ($item['href'] === 'cart.php'): ?>
                    <?php
                    $cartCount = 0;
                    try {
                        $cStmt = $database->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE customer_id = :cid");
                        $cStmt->execute(['cid' => $customerId]);
                        $cartCount = (int) $cStmt->fetchColumn();
                    } catch (\Throwable $e) {}
                    if ($cartCount > 0):
                    ?>
                        <span class="badge rounded-pill ms-auto" style="background: var(--brand-primary); font-size:0.6rem; min-width:18px;">
                            <?php echo $cartCount; ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer mt-auto">
        <a href="logout.php" class="nav-link text-danger fw-semibold">
            <span class="nav-icon"><i class="bi bi-box-arrow-right"></i></span>
            Sign Out
        </a>
    </div>
</aside>

<!-- Top Navbar -->
<header class="customer-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm border-0 d-lg-none" onclick="toggleSidebar()" aria-label="Menu">
            <i class="bi bi-list fs-4"></i>
        </button>
        <h1 class="page-title mb-0"><?php echo e($pageTitle ?? 'Portal'); ?></h1>
    </div>

    <div class="navbar-actions">
        <!-- Notification bell -->
        <a href="notifications.php" class="notif-btn" title="Notifications">
            <i class="bi bi-bell fs-5"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="badge-dot"></span>
            <?php endif; ?>
        </a>

        <!-- User chip -->
        <a href="profile.php" class="user-chip">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($customerName); ?>&background=10b981&color=fff"
                 width="30" height="30" alt="Avatar">
            <span class="user-name d-none d-sm-inline"><?php echo e($customerName); ?></span>
        </a>
    </div>
</header>

<!-- Mobile overlay -->
<div class="d-lg-none position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50"
     id="sidebarOverlay" style="z-index:999; display:none!important;"
     onclick="toggleSidebar()"></div>

<script>
function toggleSidebar() {
    const sidebar  = document.getElementById('customerSidebar');
    const overlay  = document.getElementById('sidebarOverlay');
    const isOpen   = sidebar.classList.toggle('show');
    overlay.style.display = isOpen ? 'block' : 'none';
}
</script>
