<?php
session_start();
require_once '../backend/db.php';

// 1. SECURITY SHIELD
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$admin_display_name = $_SESSION['admin_name'] ?? 'Admin';
$success_msg = "";
$error_msg = "";

// 2. IMPORT SYSTEM DATABASE LINK
require_once '../backend/db.php';

// 3. ORDER STATUS & DELETION ACTION CONTROLLER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? 'Pending';

        try {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $success_msg = "Order status updated to tracking metric: **$new_status**.";
        } catch (PDOException $e) {
            $error_msg = "Status optimization failure: " . $e->getMessage();
        }
    }

    if ($action === 'delete') {
        $order_id = intval($_POST['order_id'] ?? 0);
        try {
            $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $success_msg = "Order trace fully dropped from history tracking.";
        } catch (PDOException $e) {
            $error_msg = "Order dropping exception: " . $e->getMessage();
        }
    }
}

// 4. FETCH ENRICHED ORDERS DATA (With relational Product names)
$orders = [];
try {
    $stmt = $db->query("SELECT o.id, o.customer_name, o.quantity, o.total_price, o.status, o.order_date, p.product_name 
                        FROM orders o 
                        LEFT JOIN products p ON o.product_id = p.id 
                        ORDER BY o.id DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Data pipeline read error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Orders Control</title>
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
            <div class="mx-auto d-none d-md-block" style="width: 300px;">
                <input type="text" class="form-control" placeholder="Search orders...">
            </div>
            <div class="d-flex align-items-center gap-3">
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
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="user.php" class="nav-link"><i class="bi bi-people me-2"></i> Users</a>
            <a href="products.php" class="nav-link"><i class="bi bi-box-seam me-2"></i> Products</a>
            <a href="orders.php" class="nav-link active"><i class="bi bi-bag-check me-2"></i> Orders</a>
            <a href="reports.php" class="nav-link"><i class="bi bi-graph-up me-2"></i> Reports</a>
            <a href="#" class="nav-link"><i class="bi bi-gear me-2"></i> Settings</a>
            <hr class="text-white-50 mx-3">
            <a href="logout.php" class="nav-link text-danger fw-semibold"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a>
        </div>
    </div>

    <div class="main-content">
        <h1 class="fw-bold text-primary mb-4">Incoming Client Orders</h1>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4 shadow-sm"><?php echo $success_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm"><?php echo $error_msg; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product Item</th>
                                <th>Qty</th>
                                <th>Gross Total</th>
                                <th>Status</th>
                                <th>Date Placed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($orders)): foreach($orders as $ord): ?>
                            <tr>
                                <td class="fw-bold">#ORD-<?php echo $ord['id']; ?></td>
                                <td class="fw-medium"><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($ord['product_name'] ?? 'Deleted Product Item'); ?></td>
                                <td><?php echo $ord['quantity']; ?>x</td>
                                <td class="fw-bold text-primary">$<?php echo number_format($ord['total_price'], 2); ?></td>
                                <td>
                                    <?php
                                        $badge_map = ['Pending'=>'bg-warning text-dark', 'Processing'=>'bg-info text-white', 'Completed'=>'bg-success text-white', 'Cancelled'=>'bg-danger text-white'];
                                        $badge_style = $badge_map[$ord['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badge_style; ?>"><?php echo $ord['status']; ?></span>
                                </td>
                                <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($ord['order_date'])); ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form action="" method="POST" class="d-inline-flex gap-1">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="Pending" <?php if($ord['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="Processing" <?php if($ord['status'] === 'Processing') echo 'selected'; ?>>Processing</option>
                                                <option value="Completed" <?php if($ord['status'] === 'Completed') echo 'selected'; ?>>Completed</option>
                                                <option value="Cancelled" <?php if($ord['status'] === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                        
                                        <form action="" method="POST" onsubmit="return confirm('Archive order entry permanently?');" class="d-inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center py-5 text-muted">No transactional order parameters captured.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-5 py-4 bg-dark text-white-50">
        <div class="container-fluid px-4 text-center text-md-start">
            <p class="small mb-0">&copy; 2026 AdminHub. System tracking operational grid complete.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>