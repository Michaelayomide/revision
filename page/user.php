<?php
require_once __DIR__ . '/../config/init.php';
require_admin();

$pageTitle = 'Users';
$roles = ['Admin', 'Editor', 'User'];
$statuses = ['Active', 'Inactive'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('user.php');
    }

    $action = $_POST['action'] ?? '';
    $userId = (int) ($_POST['user_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = in_array($_POST['role'] ?? 'User', $roles, true) ? $_POST['role'] : 'User';
    $status = in_array($_POST['status'] ?? 'Active', $statuses, true) ? $_POST['status'] : 'Active';

    try {
        if ($action === 'create') {
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'A valid name and email address are required.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);
            if ((int) $stmt->fetchColumn() > 0) {
                flash('danger', 'A user with that email already exists.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare(
                'INSERT INTO users (name, email, role, status) VALUES (:name, :email, :role, :status)'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'status' => $status,
            ]);
            flash('success', 'User created successfully.');
        } elseif ($action === 'update') {
            if ($userId <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('danger', 'A valid user, name, and email address are required.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare('SELECT COUNT(*) FROM users WHERE email = :email AND id <> :id');
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ((int) $stmt->fetchColumn() > 0) {
                flash('danger', 'That email belongs to another user.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare(
                'UPDATE users SET name = :name, email = :email, role = :role, status = :status WHERE id = :id'
            );
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'id' => $userId,
            ]);
            flash('success', 'User updated successfully.');
        } elseif ($action === 'delete') {
            if ($userId <= 0) {
                flash('danger', 'Invalid user selected.');
                redirect_to('user.php');
            }

            $stmt = $database->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $userId]);
            flash('success', 'User deleted successfully.');
        }
    } catch (PDOException $e) {
        flash('danger', 'User database operation failed.');
    }

    redirect_to('user.php');
}

$members = [];
$loadError = '';

try {
    $stmt = $database->prepare('SELECT id, name, email, role, status, joined_at FROM users ORDER BY id DESC');
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Users could not be loaded. Check that the users table exists.';
}

$flashMessages = consume_flash_messages();

$pageScripts = <<<'HTML'
<script>
function filterUsers() {
    const searchVal = document.getElementById('searchInput').value.toLowerCase();
    const roleVal = document.getElementById('roleFilter').value;
    const statusVal = document.getElementById('statusFilter').value;
    document.querySelectorAll('.user-row').forEach(row => {
        const name = row.querySelector('.user-name').textContent.toLowerCase();
        const email = row.querySelector('.user-email').textContent.toLowerCase();
        const role = row.querySelector('.user-role').textContent.trim();
        const status = row.querySelector('.user-status').textContent.trim();
        row.style.display = ((name.includes(searchVal) || email.includes(searchVal)) && (!roleVal || role === roleVal) && (!statusVal || status === statusVal)) ? '' : 'none';
    });
}

function prepareAddUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userActionToken').value = 'create';
    document.getElementById('userIdToken').value = '';
    document.getElementById('userForm').reset();
}

function editUser(button) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userActionToken').value = 'update';
    document.getElementById('userIdToken').value = button.dataset.id;
    document.getElementById('nameInput').value = button.dataset.name;
    document.getElementById('emailInput').value = button.dataset.email;
    document.getElementById('roleInput').value = button.dataset.role;
    document.getElementById('statusInput').value = button.dataset.status;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function deleteUser(id) {
    if (confirm('Delete this user?')) {
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary mb-0">Users</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="prepareAddUserModal()">
            <i class="bi bi-plus-lg me-2"></i> New User
        </button>
    </div>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if ($loadError !== ''): ?>
        <div class="alert alert-warning shadow-sm"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-5">
            <input type="text" id="searchInput" class="form-control" placeholder="Search by name or email..." onkeyup="filterUsers()">
        </div>
        <div class="col-md-3">
            <select class="form-select" id="roleFilter" onchange="filterUsers()">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo e($role); ?>"><?php echo e($role); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <select class="form-select" id="statusFilter" onchange="filterUsers()">
                <option value="">All Statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($members): ?>
                            <?php foreach ($members as $member): ?>
                                <tr class="user-row">
                                    <td class="fw-semibold">#<?php echo e($member['id']); ?></td>
                                    <td class="user-name"><?php echo e($member['name']); ?></td>
                                    <td class="user-email"><?php echo e($member['email']); ?></td>
                                    <td><span class="badge bg-secondary user-role"><?php echo e($member['role']); ?></span></td>
                                    <td><span class="badge bg-success user-status"><?php echo e($member['status']); ?></span></td>
                                    <td class="small text-muted"><?php echo e(date('M d, Y', strtotime($member['joined_at'] ?? 'now'))); ?></td>
                                    <td class="text-nowrap">
                                        <button
                                            class="btn btn-sm btn-outline-primary"
                                            data-id="<?php echo e($member['id']); ?>"
                                            data-name="<?php echo e($member['name']); ?>"
                                            data-email="<?php echo e($member['email']); ?>"
                                            data-role="<?php echo e($member['role']); ?>"
                                            data-status="<?php echo e($member['status']); ?>"
                                            onclick="editUser(this)">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo e((int) $member['id']); ?>)">Delete</button>
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

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="userForm">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" id="userActionToken" value="create">
                <input type="hidden" name="user_id" id="userIdToken">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="nameInput">Full Name</label>
                        <input type="text" class="form-control" name="name" id="nameInput" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="emailInput">Email</label>
                        <input type="email" class="form-control" name="email" id="emailInput" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="roleInput">Role</label>
                            <select class="form-select" name="role" id="roleInput">
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo e($role); ?>"><?php echo e($role); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold" for="statusInput">Status</label>
                            <select class="form-select" name="status" id="statusInput">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo e($status); ?>"><?php echo e($status); ?></option>
                                <?php endforeach; ?>
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

<form method="POST" id="deleteUserForm" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="user_id" id="deleteUserId">
</form>

<?php include __DIR__ . '/../components/footer.php'; ?>
