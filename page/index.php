<?php
session_start();
require_once '../backend/auth_check.php';

// 1. CENTRALIZED DATABASE CONNECTION
require_once '../backend/db.php';

// 2. SECURITY SHIELD
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$admin_display_name = $_SESSION['admin_name'] ?? 'Admin';

// 3. INACTIVITY TIMEOUT (15 minutes)
$max_idle_time = 900; 
if (isset($_SESSION['last_activity'])) {
    $idle_duration = time() - $_SESSION['last_activity'];
    if ($idle_duration > $max_idle_time) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=Session+expired+due+to+inactivity.+Please+sign+in+again.");
        exit();
    }
}
$_SESSION['last_activity'] = time();

// Universal database connection handler safeguard
$database = $db ?? $pdo;

// 4. FETCH LIVE METRICS (Dynamically pulls from all tables)
try {
    // Count Admins
    $query = $database->query("SELECT COUNT(*) FROM admins"); 
    $total_admins = $query->fetchColumn();
    
    // Count Users
    $user_query = $database->query("SELECT COUNT(*) FROM users");
    $total_users = $user_query ? $user_query->fetchColumn() : 0;

    // Count Total Unique Products
    $product_query = $database->query("SELECT COUNT(*) FROM products");
    $total_products = $product_query ? $product_query->fetchColumn() : 0;

    // Count Active Pending Orders requiring review
    $order_query = $database->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
    $pending_orders = $order_query ? $order_query->fetchColumn() : 0;

} catch (PDOException $e) {
    $total_admins = "0"; 
    $total_users = "0";
    $total_products = "0";
    $pending_orders = "0";
}

// 5. TIME-BASED GREETING
date_default_timezone_set('Africa/Lagos'); 
$hour = date('H');
$greeting = ($hour < 12) ? "Good morning" : (($hour < 17) ? "Good afternoon" : "Good evening");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar navbar-dark fixed-top py-3 shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="navbar-toggler me-3 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand fw-bold fs-3" href="index.php">AdminHub</a>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <input class="form-control rounded-pill" type="search" placeholder="Search anything..." style="width: 260px;">
                <div class="d-flex gap-3 text-white fs-5">
                    <i class="bi bi-bell"></i>
                    <i class="bi bi-envelope"></i>
                </div>
                <div class="d-flex align-items-center gap-2 text-white">
                    <img src="https://via.placeholder.com/40" class="rounded-circle" alt="Avatar">
                    <div class="d-none d-sm-block">
                        <small class="fw-bold"><?php echo htmlspecialchars($admin_display_name); ?></small><br>
                        <small class="text-success">● Online</small>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="sidebar d-none d-lg-block">
        <div class="nav flex-column pt-3">
            <a href="index.php" class="nav-link active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="user.php" class="nav-link"><i class="bi bi-people me-2"></i> Users</a>
            <a href="products.php" class="nav-link"><i class="bi bi-box-seam me-2"></i> Products</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-bag-check me-2"></i> Orders</a>
            <a href="stock.php" class="nav-link"><i class="bi bi-boxes me-2"></i> Stock</a>
            <a href="#" class="nav-link"><i class="bi bi-graph-up me-2"></i> Reports</a>
            <a href="#" class="nav-link"><i class="bi bi-gear me-2"></i> Settings</a>
            <hr class="text-white-50 mx-3">
            <a href="logout.php" class="nav-link text-danger fw-semibold"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#1e2937; color:white; width:270px;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title fw-bold">AdminHub</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="nav flex-column">
                <a href="index.php" class="nav-link active px-4 py-3"><i class="bi bi-speedometer2 me-3"></i> Dashboard</a>
                <a href="user.php" class="nav-link px-4 py-3"><i class="bi bi-people me-3"></i> Users</a>
                <a href="products.php" class="nav-link px-4 py-3"><i class="bi bi-box-seam me-3"></i> Products</a>
                <a href="orders.php" class="nav-link px-4 py-3"><i class="bi bi-bag-check me-3"></i> Orders</a>
                <a href="stock.php" class="nav-link px-4 py-3"><i class="bi bi-boxes me-3"></i> Stock</a>
                <a href="#" class="nav-link px-4 py-3"><i class="bi bi-graph-up me-3"></i> Reports</a>
                <a href="#" class="nav-link px-4 py-3"><i class="bi bi-gear me-3"></i> Settings</a>
                <a href="logout.php" class="nav-link text-danger fw-semibold px-4 py-3"><i class="bi bi-box-arrow-right me-3"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div id="dashboard-page">
            
            <div class="welcome-header">
                <h2 class="fw-bold mb-1"><?php echo $greeting . ", " . htmlspecialchars($admin_display_name); ?>! 👋</h2>
                <p class="mb-0 opacity-90">Here's what's happening with your platform today.</p>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                        <div class="card-body">
                            <i class="bi bi-shield-lock-fill fs-1 mb-3"></i>
                            <h5>Total Admins</h5>
                            <h2 class="fw-bold"><?php echo $total_admins; ?></h2>
                            <small class="opacity-90">Active administrative records</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                        <div class="card-body">
                            <i class="bi bi-people-fill fs-1 mb-3"></i>
                            <h5>Registered Users</h5>
                            <h2 class="fw-bold"><?php echo $total_users; ?></h2>
                            <small class="opacity-90">Total active app members</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <div class="card-body">
                            <i class="bi bi-box-seam-fill fs-1 mb-3"></i>
                            <h5>Total Products</h5>
                            <h2 class="fw-bold"><?php echo $total_products; ?></h2>
                            <small class="opacity-90">Unique items listed in catalog</small>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                        <div class="card-body">
                            <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                            <h5>Pending Orders</h5>
                            <h2 class="fw-bold"><?php echo $pending_orders; ?></h2>
                            <small class="opacity-90">Requires immediate fulfillment</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold py-3 border-bottom">Recent Activity Log</div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item py-3">
                                    <i class="bi bi-circle-fill text-success small me-2"></i>
                                    <strong><?php echo htmlspecialchars($admin_display_name); ?></strong> securely verified via authentication check.
                                    <span class="text-muted float-end small">Just now</span>
                                </div>
                                <div class="list-group-item py-3">
                                    <i class="bi bi-circle-fill text-primary small me-2"></i>
                                    System tracking infrastructure connected to database successfully.
                                    <span class="text-muted float-end small">10 mins ago</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header bg-white fw-bold py-3">Quick Actions</div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="user.php" class="btn btn-outline-primary btn-lg">
                                    <i class="bi bi-person me-2"></i> Add new user
                                </a>
                                <button class="btn btn-outline-success btn-lg">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i> Generate Report
                                </button>
                                <a href="products.php" class="btn btn-outline-info btn-lg">
                                    <i class="bi bi-box-seam me-2"></i> Manage Products
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold text-primary">AdminHub</h5>
                    <p class="text-light-50">Powerful admin dashboard for managing your platform efficiently.</p>
                    <small>&copy; 2026 AdminHub. All rights reserved.</small>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold">Navigation</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light-50">Dashboard</a></li>
                        <li><a href="user.php" class="text-light-50">Users</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="fw-bold">Resources</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light-50">Support</a></li>
                        <li><a href="#" class="text-light-50">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="fw-bold">Connect</h6>
                    <div class="d-flex gap-3 fs-4">
                        <i class="bi bi-twitter"></i>
                        <i class="bi bi-facebook"></i>
                        <i class="bi bi-linkedin"></i>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>