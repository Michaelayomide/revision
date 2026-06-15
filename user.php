<?php
session_start();

// 1. SECURITY SHIELD (Ensure only logged-in admins can view this directory)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$admin_display_name = $_SESSION['username'] ?? $_SESSION['admin_name'] ?? $_SESSION['email'] ?? 'Admin';

// Set up status alert flags
$success_msg = "";
$error_msg = "";

$db = null; 

// 2. DATABASE CONNECTION
try {
   
    $database_name = "revsion"; 
    
    $db = new PDO("mysql:host=localhost;dbname=$database_name;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $error_msg = "Database Connection Error: " . $e->getMessage();
}

// 3. MULTI-ACTION ROUTER LAYER (Create, Update, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if (!$db) {
        $error_msg = "Action blocked: Database connection is not active.";
    } else {
        $action = $_POST['action'];

        // --- A. CREATE NEW USER WORKFLOW ---
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'User';
            $status = $_POST['status'] ?? 'Active';

            if (empty($name) || empty($email)) {
                $error_msg = "Full Name and Email Address are required fields.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "Please provide a valid email layout.";
            } else {
                try {
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
                    $check_stmt->execute([$email]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_msg = "Registration conflict: A user with this email already exists.";
                    } else {
                        $insert_stmt = $db->prepare("INSERT INTO users (name, email, role, status) VALUES (?, ?, ?, ?)");
                        $insert_stmt->execute([$name, $email, $role, $status]);
                        $success_msg = "Fantastic! New user successfully registered.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Operational failure while saving: " . $e->getMessage();
                }
            }
        }

        // --- B. UPDATE EXISTING USER WORKFLOW ---
        elseif ($action === 'update') {
            $user_id = intval($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'User';
            $status = $_POST['status'] ?? 'Active';

            if ($user_id <= 0 || empty($name) || empty($email)) {
                $error_msg = "All form details are required to complete the user modification.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_msg = "Please provide a valid email format.";
            } else {
                try {
                    // Make sure the email isn't already taken by *another* user ID
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                    $check_stmt->execute([$email, $user_id]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_msg = "Conflict: This email address is already assigned to a different user.";
                    } else {
                        $update_stmt = $db->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
                        $update_stmt->execute([$name, $email, $role, $status, $user_id]);
                        $success_msg = "Excellent! User profile parameters updated successfully.";
                    }
                } catch (PDOException $e) {
                    $error_msg = "Operational failure while updating: " . $e->getMessage();
                }
            }
        }

        // --- C. DELETE USER WORKFLOW ---
        elseif ($action === 'delete') {
            $user_id = intval($_POST['user_id'] ?? 0);
            if ($user_id > 0) {
                try {
                    $delete_stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                    $delete_stmt->execute([$user_id]);
                    $success_msg = "User entry successfully expunged from the database system.";
                } catch (PDOException $e) {
                    $error_msg = "Operational failure during deletion processing: " . $e->getMessage();
                }
            } else {
                $error_msg = "Invalid user identifier passed to delete processing node.";
            }
        }
    }
}

// 4. FETCH CURRENT MEMBERS LIST
$members = [];
if ($db && empty($error_msg)) {
    try {
        $stmt = $db->query("SELECT id, name, email, role, status, joined_at FROM users ORDER BY id DESC");
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_msg = "Failed to load directory items: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminHub - Users Directory</title>
    
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
            <a href="user.php" class="nav-link active"><i class="bi bi-people me-2"></i> Users</a>
            <a href="products.php" class="nav-link"><i class="bi bi-box-seam me-2"></i> Products</a>
            <a href="orders.php" class="nav-link"><i class="bi bi-bag-check me-2"></i> Orders</a>
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
                <a href="user.php" class="nav-link active px-4 py-3"><i class="bi bi-people me-3"></i> Users</a>
                <a href="products.php" class="nav-link px-4 py-3"><i class="bi bi-box-seam me-3"></i> Products</a>
                <a href="orders.php" class="nav-link px-4 py-3"><i class="bi bi-bag-check me-3"></i> Orders</a>
                <a href="logout.php" class="nav-link text-danger fw-semibold px-4 py-3"><i class="bi bi-box-arrow-right me-3"></i> Log Out</a>
            </div>
        </div>
    </div>

    <div class="main-content">
        <h1 class="fw-bold text-primary mb-4">Users Management</h1>

        <?php if(!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email..." onkeyup="filterUsers()">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="roleFilter" onchange="filterUsers()">
                    <option value="">All Roles</option>
                    <option value="Admin">Admin</option>
                    <option value="Editor">Editor</option>
                    <option value="User">User</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter" onchange="filterUsers()">
                    <option value="">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-1">
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareAddModal()">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="usersTable">
                        <thead class="table-light">
                            <tr>
                                <th># ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $user): ?>
                                    <tr class="user-row">
                                        <td class="user-id fw-semibold"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="user-name"><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td class="user-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <?php 
                                                $role = htmlspecialchars($user['role']);
                                                $badge_color = ($role === 'Admin') ? 'bg-primary' : (($role === 'Editor') ? 'bg-info text-dark' : 'bg-secondary');
                                            ?>
                                            <span class="badge <?php echo $badge_color; ?> user-role"><?php echo $role; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                $status = htmlspecialchars($user['status']);
                                                $status_color = ($status === 'Active') ? 'bg-success' : 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $status_color; ?> user-status"><?php echo $status; ?></span>
                                        </td>
                                        <td>
                                            <?php 
                                                echo htmlspecialchars(date('d Jan 2026', strtotime($user['joined_at'] ?? 'now'))); 
                                            ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-id="<?php echo $user['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                        data-role="<?php echo htmlspecialchars($user['role']); ?>"
                                                        data-status="<?php echo htmlspecialchars($user['status']); ?>"
                                                        onclick="editUser(this)">
                                                    Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="noResultsRow">
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i> No website members found matching your database records.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" id="modalUserForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formActionToken" value="create">
                        <input type="hidden" name="user_id" id="formUserIdToken" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <input type="text" class="form-control" name="name" id="nameInput" required placeholder="Enter full name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <input type="email" class="form-control" name="email" id="emailInput" required placeholder="user@example.com">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Role</label>
                                <select class="form-select" name="role" id="roleInput">
                                    <option value="User">User</option>
                                    <option value="Editor">Editor</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select class="form-select" name="status" id="statusInput">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <form id="deleteTrackingForm" action="" method="POST" style="display:none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="user_id" id="deleteTargetId" value="">
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
        function filterUsers() {
            const searchVal = document.getElementById('searchInput').value.toLowerCase();
            const roleVal = document.getElementById('roleFilter').value;
            const statusVal = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const name = row.querySelector('.user-name').textContent.toLowerCase();
                const email = row.querySelector('.user-email').textContent.toLowerCase();
                const role = row.querySelector('.user-role').textContent;
                const status = row.querySelector('.user-status').textContent;
                
                const matchesSearch = name.includes(searchVal) || email.includes(searchVal);
                const matchesRole = roleVal === "" || role === roleVal;
                const matchesStatus = statusVal === "" || status === statusVal;
                
                row.style.display = (matchesSearch && matchesRole && matchesStatus) ? "" : "none";
            });
        }

        function prepareAddModal() {
            document.getElementById('modalTitle').textContent = "Add New User";
            document.getElementById('formActionToken').value = "create";
            document.getElementById('formUserIdToken').value = "";
            document.getElementById('modalUserForm').reset();
        }

        // TRIGGERED ACTION: Reads values off button dataset properties and applies them inside the modal layout
        function editUser(buttonElement) {
            document.getElementById('modalTitle').textContent = "Edit Existing User Details";
            document.getElementById('formActionToken').value = "update";
            
            const id = buttonElement.getAttribute('data-id');
            const name = buttonElement.getAttribute('data-name');
            const email = buttonElement.getAttribute('data-email');
            const role = buttonElement.getAttribute('data-role');
            const status = buttonElement.getAttribute('data-status');
            
            document.getElementById('formUserIdToken').value = id;
            document.getElementById('nameInput').value = name;
            document.getElementById('emailInput').value = email;
            document.getElementById('roleInput').value = role;
            document.getElementById('statusInput').value = status;
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }

        // TRIGGERED ACTION: Confirms operation intent, primes layout token, and targets the hidden deletion tracking form
        function deleteUser(id) {
            if (confirm("🚨 WARNING: Are you completely certain you want to permanently delete this user directory account record?")) {
                document.getElementById('deleteTargetId').value = id;
                document.getElementById('deleteTrackingForm').submit();
            }
        }
    </script>
</body>
</html>