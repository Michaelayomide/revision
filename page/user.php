<?php
require_once __DIR__ . '/../config/init.php';
require_admin();
require_role('primary_admin', 'secondary_admin'); 

$pageTitle = 'User Management';
$roles = ['primary_admin', 'secondary_admin', 'editor'];
$statuses = ['Active', 'Suspended'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('user.php');
    }

    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'editor';
    $status = $_POST['status'] ?? 'Active';
    $role = in_array($role, $roles, true) ? $role : 'editor';
    $status = in_array($status, $statuses, true) ? $status : 'Active';

    try {
        // --- ACTION 1: UPDATE STAFF PERMISSIONS ---
        if ($action === 'update_user') {
            if ($userId <= 0) {
                flash('danger', 'Invalid user parameters chosen.');
                redirect_to('user.php');
            }

            // Core Security Guard: Prevent self-demotion or self-suspension
            if ($userId === (int)($_SESSION['admin_id'] ?? 0)) {
                flash('danger', 'You cannot alter your own admin privileges or account status.');
                redirect_to('user.php');
            }

            // System Safety Guard: Protect primary root admins (ID 1 and ID 2) from being altered or suspended
            if (in_array($userId, [1, 2], true)) {
                flash('danger', 'Access Denied: Root administrator accounts (ID 1 & 2) cannot be modified or suspended.');
                redirect_to('user.php');
            }

            // Fetch target user's current role to check access
            $stmt = $database->prepare("SELECT role FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                flash('danger', 'User not found.');
                redirect_to('user.php');
            }

            // Secondary Admin checks
            if (is_secondary_admin()) {
                if ($targetUser['role'] !== 'editor') {
                    flash('danger', 'Access Denied: Secondary Admins can only modify Editors.');
                    redirect_to('user.php');
                }
                if ($role !== 'editor') {
                    flash('danger', 'Access Denied: Secondary Admins cannot promote/change roles to non-editor.');
                    redirect_to('user.php');
                }
            }

            $stmt = $database->prepare("UPDATE admins SET role = :role, status = :status WHERE id = :id");
            $stmt->execute([
                'role' => $role,
                'status' => $status,
                'id' => $userId
            ]);
            flash('success', 'User configurations modified successfully.');
        }
        
        // --- ACTION 2: ADD NEW STAFF ---
        elseif ($action === 'add_user') {
            $fullname = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($fullname) || empty($email) || empty($password)) {
                flash('danger', 'All registration fields are required.');
                redirect_to('user.php');
            }

            if (is_secondary_admin() && $role !== 'editor') {
                flash('danger', 'Access Denied: Secondary Admins can only create Editors.');
                redirect_to('user.php');
            }

            // Hash the password
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            // Check if email already exists
            $stmt = $database->prepare("SELECT id FROM admins WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                flash('danger', 'This email is already in use by another admin.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare("INSERT INTO admins (fullname, email, password, role, status) VALUES (:fullname, :email, :password, :role, :status)");
            $stmt->execute([
                'fullname' => $fullname,
                'email' => $email,
                'password' => $hashed,
                'role' => $role,
                'status' => $status
            ]);
            flash('success', 'Staff account created successfully.');
        }

        // --- ACTION 3: DELETE STAFF ---
        elseif ($action === 'delete_user') {
            if ($userId <= 0) {
                flash('danger', 'Invalid user selected.');
                redirect_to('user.php');
            }

            if (in_array($userId, [1, 2], true)) {
                flash('danger', 'Access Denied: Root administrator accounts (ID 1 & 2) cannot be deleted.');
                redirect_to('user.php');
            }

            if ($userId === (int)($_SESSION['admin_id'] ?? 0)) {
                flash('danger', 'You cannot delete your own account.');
                redirect_to('user.php');
            }

            // Fetch target user's current role to check access
            $stmt = $database->prepare("SELECT role FROM admins WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                flash('danger', 'User not found.');
                redirect_to('user.php');
            }

            if (is_secondary_admin() && $targetUser['role'] !== 'editor') {
                flash('danger', 'Access Denied: Secondary Admins can only delete Editors.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare("DELETE FROM admins WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            flash('success', 'User account removed successfully.');
        }

    } catch (PDOException $e) {
        flash('danger', 'Database exception encountered operating on staff: ' . $e->getMessage());
    }

    redirect_to('user.php');
}

$users = [];
try {
    $stmt = $database->prepare("SELECT id, fullname, email, role, status, created_at FROM admins ORDER BY id DESC");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Users data layer execution failed. Verify database structure.';
}

$flashMessages = consume_flash_messages();

$pageScripts = <<<'HTML'
<script>
function editUserPermissions(button) {
    document.getElementById('modalUserId').value = button.dataset.id;
    document.getElementById('modalUsername').textContent = button.dataset.fullname;
    document.getElementById('modalRoleInput').value = button.dataset.role;
    document.getElementById('modalStatusInput').value = button.dataset.status;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function confirmDeleteUser(id, name) {
    if (confirm("Are you sure you want to delete staff account '" + name + "'? This action cannot be undone.")) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}
</script>
HTML;

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <div class="page-header">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Staff</li>
                </ol>
            </nav>
            <h1 class="page-title">System Users</h1>
        </div>
        <div class="page-header-actions">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add Staff
            </button>
        </div>
    </div>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if (isset($loadError) && $loadError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role Access</th>
                            <th>Status Flag</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): ?>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                // Enforce root rules on display layer
                                $userRole = (in_array((int)$user['id'], [1, 2], true)) ? 'primary_admin' : $user['role'];
                                $userStatus = (in_array((int)$user['id'], [1, 2], true)) ? 'Active' : $user['status'];

                                $badgeColor = $userRole === 'primary_admin' ? 'bg-danger status' : ($userRole === 'secondary_admin' ? 'bg-warning status' : 'bg-info status');
                                $statusColor = $userStatus === 'Active' ? 'bg-success status' : 'bg-secondary status';
                                
                                // Check if current admin can manage this user
                                $canManage = false;
                                if (!in_array((int)$user['id'], [1, 2], true)) {
                                    if (is_primary_admin()) {
                                        $canManage = true;
                                    } elseif (is_secondary_admin()) {
                                        $canManage = ($userRole === 'editor');
                                    }
                                }
                                ?>
                                <tr>
                                    <td class="fw-semibold">#<?php echo e($user['id']); ?></td>
                                    <td class="fw-medium"><?php echo e($user['fullname']); ?></td>
                                    <td><?php echo e($user['email']); ?></td>
                                    <td><span class="badge <?php echo e($badgeColor); ?>"><?php echo e(str_replace('_', ' ', $userRole)); ?></span></td>
                                    <td><span class="badge <?php echo e($statusColor); ?>"><?php echo e($userStatus); ?></span></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y', strtotime($user['created_at']))); ?></td>
                                    <td>
                                        <?php if ($canManage): ?>
                                            <button 
                                                class="btn btn-sm btn-outline-secondary me-1"
                                                data-id="<?php echo e($user['id']); ?>"
                                                data-fullname="<?php echo e($user['fullname']); ?>"
                                                data-role="<?php echo e($userRole); ?>"
                                                data-status="<?php echo e($userStatus); ?>"
                                                onclick="editUserPermissions(this)">
                                                Modify
                                            </button>
                                            <button 
                                                class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDeleteUser(<?php echo e($user['id']); ?>, '<?php echo e(addslashes($user['fullname'])); ?>')">
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="bi bi-lock-fill"></i> Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Hidden form for deleting staff -->
<form id="deleteUserForm" method="POST" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus text-primary"></i> Add New Staff Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="addFullname">Full Name</label>
                        <input type="text" class="form-control" name="fullname" id="addFullname" required placeholder="e.g. John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="addEmail">Email Address</label>
                        <input type="email" class="form-control" name="email" id="addEmail" required placeholder="e.g. john@adminhub.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="addPassword">Password</label>
                        <input type="password" class="form-control" name="password" id="addPassword" required placeholder="At least 8 characters">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="addRoleInput">Security Role</label>
                        <select class="form-select" name="role" id="addRoleInput">
                            <?php if (is_primary_admin()): ?>
                                <option value="secondary_admin">Secondary Admin</option>
                                <option value="editor" selected>Editor</option>
                            <?php else: ?>
                                <option value="editor" selected>Editor</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="addStatusInput">Account Status</label>
                        <select class="form-select" name="status" id="addStatusInput">
                            <?php foreach ($statuses as $statusOption): ?>
                                <option value="<?php echo e($statusOption); ?>"><?php echo e($statusOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Modify Permissions -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-lock text-primary"></i> Modify Security Profile <span class="text-muted" id="modalUsername"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="modalRoleInput">Assigned Security Role</label>
                        <select class="form-select" name="role" id="modalRoleInput">
                            <?php if (is_primary_admin()): ?>
                                <option value="primary_admin">Super Admin (Primary)</option>
                                <option value="secondary_admin">Secondary Admin</option>
                                <option value="editor">Editor</option>
                            <?php else: ?>
                                <option value="editor">Editor</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="modalStatusInput">Account Access Status</label>
                        <select class="form-select" name="status" id="modalStatusInput">
                            <?php foreach ($statuses as $statusOption): ?>
                                <option value="<?php echo e($statusOption); ?>"><?php echo e($statusOption); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Commit Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>