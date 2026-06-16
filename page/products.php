<?php
session_start();
require_once '../backend/db.php';

/** @var PDO $db */
if (!isset($db) || !$db instanceof PDO) {
    die("Database connection failure.");
}

// 1. SECURITY SHIELD
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$admin_display_name = $_SESSION['username'] ?? $_SESSION['admin_name'] ?? $_SESSION['email'] ?? 'Admin';

// Set up alert notification catchers
$success_msg = "";
$error_msg = "";

// 2. IMPORT SYSTEM DATABASE LINK
// Database link already imported above via require_once

// 3. PRODUCT CRUD CONTROLLER ROUTER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- A. CREATE WORKFLOW ---
    if ($action === 'create') {
        $name  = trim($_POST['product_name'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'Active';

        if (empty($name) || $price <= 0) {
            $error_msg = "Product Name and a valid positive Price are required.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO products (product_name, price, stock, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $price, $stock, $status]);
                $success_msg = "Superb! Product inventory listing created successfully.";
            } catch (PDOException $e) {
                $error_msg = "Database saving error: " . $e->getMessage();
            }
        }
    }

    // --- B. UPDATE WORKFLOW ---
    elseif ($action === 'update') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $name       = trim($_POST['product_name'] ?? '');
        $price      = floatval($_POST['price'] ?? 0);
        $stock      = intval($_POST['stock'] ?? 0);
        $status     = $_POST['status'] ?? 'Active';

        if ($product_id <= 0 || empty($name) || $price <= 0) {
            $error_msg = "All metrics must be valid to complete the update process.";
        } else {
            try {
                $stmt = $db->prepare("UPDATE products SET product_name = ?, price = ?, stock = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $price, $stock, $status, $product_id]);
                $success_msg = "Excellent! Product attributes updated smoothly.";
            } catch (PDOException $e) {
                $error_msg = "Database update error: " . $e->getMessage();
            }
        }
    }

    // --- C. DELETE WORKFLOW ---
    elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $success_msg = "Product successfully scrubbed from catalog database.";
            } catch (PDOException $e) {
                $error_msg = "Database deletion error: " . $e->getMessage();
            }
        }
    }
}

// 4. READ / FETCH PRODUCTS GRID
$products = [];
try {
    $stmt = $db->query("SELECT id, product_name, price, stock, status, created_at FROM products ORDER BY id DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Failed loading stock registry: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Products Management</title>
    
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
                <input type="text" class="form-control" placeholder="Search anything...">
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
            <a href="products.php" class="nav-link active"><i class="bi bi-box-seam me-2"></i> Products</a>
            <a href="#" class="nav-link"><i class="bi bi-bag-check me-2"></i> Orders</a>
            <a href="#" class="nav-link"><i class="bi bi-graph-up me-2"></i> Reports</a>
            <a href="#" class="nav-link"><i class="bi bi-gear me-2"></i> Settings</a>
            <hr class="text-white-50 mx-3">
            <a href="logout.php" class="nav-link text-danger fw-semibold"><i class="bi bi-box-arrow-right me-2"></i> Log Out</a>
        </div>
    </div>

    <div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background: #1e2937; color: white; width: 270px;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title fw-bold">AdminHub</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="nav flex-column">
                <a href="index.php" class="nav-link px-4 py-3"><i class="bi bi-speedometer2 me-3"></i> Dashboard</a>
                <a href="user.php" class="nav-link px-4 py-3"><i class="bi bi-people me-3"></i> Users</a>
                <a href="products.php" class="nav-link active px-4 py-3"><i class="bi bi-box-seam me-3"></i> Products</a>
                <a href="#" class="nav-link px-4 py-3"><i class="bi bi-bag-check me-3"></i> Orders</a>
                <a href="logout.php" class="nav-link text-danger fw-semibold px-4 py-3"><i class="bi bi-box-arrow-right me-3"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h1 class="fw-bold text-primary mb-4">Products Inventory</h1>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-8">
                <input type="text" id="productSearchInput" class="form-control" placeholder="Search product items by name..." onkeyup="filterProducts()">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter" onchange="filterProducts()">
                    <option value="">All Stock Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Out of Stock">Out of Stock</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#productModal" onclick="prepareAddModal()">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="productsTable">
                        <thead class="table-light">
                            <tr>
                                <th># ID</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock Level</th>
                                <th>Status</th>
                                <th>Listed On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $prod): ?>
                                    <tr class="product-row">
                                        <td class="prod-id fw-semibold"><?php echo $prod['id']; ?></td>
                                        <td class="prod-name fw-medium"><?php echo htmlspecialchars($prod['product_name']); ?></td>
                                        <td class="prod-price">$<?php echo number_format($prod['price'], 2); ?></td>
                                        <td>
                                            <span class="fw-semibold <?php echo ($prod['stock'] == 0) ? 'text-danger' : 'text-dark'; ?>">
                                                <?php echo $prod['stock']; ?> units
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                                $status = htmlspecialchars($prod['status']);
                                                $status_badge = ($status === 'Active') ? 'bg-success' : 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $status_badge; ?> prod-status"><?php echo $status; ?></span>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo date('d M Y', strtotime($prod['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-id="<?php echo $prod['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                                        data-price="<?php echo $prod['price']; ?>"
                                                        data-stock="<?php echo $prod['stock']; ?>"
                                                        data-status="<?php echo htmlspecialchars($prod['status']); ?>"
                                                        onclick="editProduct(this)">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct(<?php echo $prod['id']; ?>)">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-seam fs-1 d-block mb-2"></i> No stock items populated yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" id="modalProductForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">List New Stock Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formActionToken" value="create">
                        <input type="hidden" name="product_id" id="formProductIdToken" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Product Title</label>
                            <input type="text" class="form-control" name="product_name" id="nameInput" required placeholder="e.g. Ergonomic Keyboard">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Price ($)</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="priceInput" required placeholder="0.00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock" id="stockInput" required value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Listing Status</label>
                            <select class="form-select" name="status" id="statusInput">
                                <option value="Active">Active</option>
                                <option value="Out of Stock">Out of Stock</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteTrackingForm" action="" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="product_id" id="deleteTargetId" value="">
    </form>

    <footer class="mt-5 py-4 bg-dark text-white-50">
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="fw-bold text-white">AdminHub</h5>
                    <p class="small mb-0">&copy; 2026 AdminHub. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // LIVE DYNAMIC FILTER UTILITY ENGINE
        function filterProducts() {
            const searchVal = document.getElementById('productSearchInput').value.toLowerCase();
            const statusVal = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.product-row');
            
            rows.forEach(row => {
                const name = row.querySelector('.prod-name').textContent.toLowerCase();
                const status = row.querySelector('.prod-status').textContent;
                
                const matchesSearch = name.includes(searchVal);
                const matchesStatus = statusVal === "" || status === statusVal;
                
                row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
            });
        }

        function prepareAddModal() {
            document.getElementById('modalTitle').textContent = "List New Stock Product";
            document.getElementById('formActionToken').value = "create";
            document.getElementById('formProductIdToken').value = "";
            document.getElementById('modalProductForm').reset();
        }

        function editProduct(button) {
            document.getElementById('modalTitle').textContent = "Modify Product Attributes";
            document.getElementById('formActionToken').value = "update";
            
            document.getElementById('formProductIdToken').value = button.getAttribute('data-id');
            document.getElementById('nameInput').value = button.getAttribute('data-name');
            document.getElementById('priceInput').value = button.getAttribute('data-price');
            document.getElementById('stockInput').value = button.getAttribute('data-stock');
            document.getElementById('statusInput').value = button.getAttribute('data-status');
            
            const modal = new bootstrap.Modal(document.getElementById('productModal'));
            modal.show();
        }

        function deleteProduct(id) {
            if (confirm("🚨 WARNING: Are you sure you want to delete this product listing entry permanently?")) {
                document.getElementById('deleteTargetId').value = id;
                document.getElementById('deleteTrackingForm').submit();
            }
        }
    </script>
</body>
</html>