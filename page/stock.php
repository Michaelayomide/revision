<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Stock Management</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark fixed-top py-3 shadow-sm">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <button class="navbar-toggler me-3 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand fw-bold fs-3" href="dashboard.html">AdminHub</a>
            </div>
        </div>
    </nav>

    <!-- Desktop Sidebar -->
    <div class="sidebar d-none d-lg-block">
        <div class="nav flex-column pt-3">
            <a href="index.php" class="nav-link"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
            <a href="user.html" class="nav-link"><i class="bi bi-people me-2"></i> Users</a>
            <a href="products.html" class="nav-link"><i class="bi bi-box-seam me-2"></i> Products</a>
            <a href="orders.html" class="nav-link"><i class="bi bi-bag-check me-2"></i> Orders</a>
            <a href="stock.html" class="nav-link active"><i class="bi bi-boxes me-2"></i> Stock</a>
            <a href="report.html" class="nav-link"><i class="bi bi-graph-up me-2"></i> Reports</a>
            <a href="settings.html" class="nav-link"><i class="bi bi-gear me-2"></i> Settings</a>
        </div>
    </div>

    <!-- Mobile Offcanvas -->
    <div class="offcanvas offcanvas-start d-lg-none" id="mobileSidebar" tabindex="-1" style="background:#1e2937; color:white; width:270px;">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title fw-bold">AdminHub</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="nav flex-column">
                <a href="dashboard.html" class="nav-link px-4 py-3"><i class="bi bi-speedometer2 me-3"></i> Dashboard</a>
                <a href="user.html" class="nav-link px-4 py-3"><i class="bi bi-people me-3"></i> Users</a>
                <a href="products.html" class="nav-link px-4 py-3"><i class="bi bi-box-seam me-3"></i> Products</a>
                <a href="orders.html" class="nav-link px-4 py-3"><i class="bi bi-bag-check me-3"></i> Orders</a>
                <a href="stock.html" class="nav-link active px-4 py-3"><i class="bi bi-boxes me-3"></i> Stock</a>
                <a href="report.html" class="nav-link px-4 py-3"><i class="bi bi-graph-up me-3"></i> Reports</a>
                <a href="settings.html" class="nav-link px-4 py-3"><i class="bi bi-gear me-3"></i> Settings</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold text-primary">Stock & Inventory</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                <i class="bi bi-plus-circle me-2"></i> New Stock Movement
            </button>
        </div>

        <!-- Stock Summary -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <div class="card-body">
                        <i class="bi bi-boxes fs-1 mb-3"></i>
                        <h5>Total Stock Value</h5>
                        <h2 class="fw-bold">$124,580</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    <div class="card-body">
                        <i class="bi bi-exclamation-triangle fs-1 mb-3"></i>
                        <h5>Low Stock Items</h5>
                        <h2 class="fw-bold">23</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #ef4444, #dc2626);">
                    <div class="card-body">
                        <i class="bi bi-x-circle fs-1 mb-3"></i>
                        <h5>Out of Stock</h5>
                        <h2 class="fw-bold">9</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card stat-card text-white h-100" style="background: linear-gradient(135deg, #6366f1, #4f46e5);">
                    <div class="card-body">
                        <i class="bi bi-arrow-down-up fs-1 mb-3"></i>
                        <h5>Movements Today</h5>
                        <h2 class="fw-bold">37</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="card">
            <div class="card-header bg-white fw-bold">Current Stock Levels</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Min Stock</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>P001</td>
                            <td>Wireless Headphones</td>
                            <td>Electronics</td>
                            <td><span class="badge bg-success">142</span></td>
                            <td>20</td>
                            <td>In Stock</td>
                            <td>2 hrs ago</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="adjustStock(1)">Adjust</button>
                            </td>
                        </tr>
                        <tr>
                            <td>P002</td>
                            <td>Smart Watch</td>
                            <td>Electronics</td>
                            <td><span class="badge bg-warning">8</span></td>
                            <td>15</td>
                            <td>Low Stock</td>
                            <td>Yesterday</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="adjustStock(2)">Adjust</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sticky Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold text-primary">AdminHub</h5>
                    <p class="text-light-50">Powerful admin dashboard for managing your platform efficiently.</p>
                    <small>&copy; 2026 AdminHub. All rights reserved.</small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function adjustStock(id) {
            alert("Stock adjustment for product #" + id + " opened.");
        }
    </script>
</body>
</html>