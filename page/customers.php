<?php
require_once __DIR__ . '/../config/init.php';
require_admin();
require_role('primary_admin', 'secondary_admin');

$pageTitle         = 'Customer Accounts';
$inactivityMonths  = (int) app_setting('inactivity_months', '12');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired.');
        redirect_to('customers.php');
    }

    $action     = $_POST['action'] ?? '';
    $customerId = (int) ($_POST['customer_id'] ?? 0);

    try {
        if ($action === 'delete_customer') {
            if (!is_primary_admin()) {
                flash('danger', 'Only the Primary Admin can delete customer accounts.');
                redirect_to('customers.php');
            }
            if ($customerId <= 0) {
                flash('danger', 'Invalid customer selected.');
                redirect_to('customers.php');
            }

            // FIXED: Standardized date interval checking against MySQL baseline thresholds
            $stmt = $database->prepare(
                "SELECT id, fullname, COALESCE(last_login_at, created_at) AS last_active FROM customers WHERE id = :id LIMIT 1"
            );
            $stmt->execute(['id' => $customerId]);
            $customer = $stmt->fetch();

            if (!$customer) {
                flash('danger', 'Customer not found.');
                redirect_to('customers.php');
            }

            $lastActiveTime = new DateTime($customer['last_active']);
            $currentTime = new DateTime();
            $interval = $currentTime->diff($lastActiveTime);
            $monthsInactive = ($interval->y * 12) + $interval->m;

            if ($monthsInactive < $inactivityMonths) {
                flash('warning', "This customer is not yet eligible for deletion (last active {$monthsInactive} months ago; threshold: {$inactivityMonths} months).");
                redirect_to('customers.php');
            }

            $database->prepare("DELETE FROM customers WHERE id = :id")->execute(['id' => $customerId]);
            flash('success', "Customer account for \"{$customer['fullname']}\" has been deleted.");

        } elseif ($action === 'toggle_active') {
            if (!is_primary_admin()) {
                flash('danger', 'Only the Primary Admin can deactivate customer accounts.');
                redirect_to('customers.php');
            }
            $stmt = $database->prepare("SELECT is_active FROM customers WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $customerId]);
            $cust = $stmt->fetch();
            if ($cust) {
                $newState = (int) $cust['is_active'] === 1 ? 0 : 1;
                $database->prepare("UPDATE customers SET is_active = :state WHERE id = :id")
                    ->execute(['state' => $newState, 'id' => $customerId]);
                flash('success', 'Customer account status updated.');
            }
        }
    } catch (PDOException $e) {
        flash('danger', 'Operation failed. Please try again.');
    }

    redirect_to('customers.php');
}

// Load customers with order counts
$customers = [];
$loadError = '';

try {
    $stmt = $database->prepare(
        "SELECT
            c.id,
            c.fullname,
            c.email,
            c.is_active,
            c.last_login_at,
            c.created_at,
            COUNT(DISTINCT o.id)                                      AS order_count,
            COALESCE(SUM(o.total_amount), 0)                          AS total_spent,
            TIMESTAMPDIFF(MONTH, COALESCE(c.last_login_at, c.created_at), NOW()) AS months_inactive
         FROM customers c
         LEFT JOIN orders o ON o.customer_id = c.id AND o.order_status <> 'Cancelled'
         GROUP BY c.id, c.fullname, c.email, c.is_active, c.last_login_at, c.created_at
         ORDER BY c.created_at DESC"
    );
    $stmt->execute();
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    $loadError = 'Customers could not be loaded. Ensure the migration has been applied.';
}

$flashMessages = consume_flash_messages();

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content d-flex flex-column align-items-start justify-content-start min-vh-100">
    <div class="d-flex justify-content-between align-items-center w-100 mb-4">
        <div>
            <h1 class="fw-bold text-primary mb-0">Customer Accounts</h1>
            <small class="text-muted">Customers inactive for ≥<?php echo e($inactivityMonths); ?> months are eligible for deletion.</small>
        </div>
        <span class="badge bg-secondary fs-6"><?php echo count($customers); ?> Registered</span>
    </div>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm w-100">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php if ($loadError !== ''): ?>
        <div class="alert alert-warning shadow-sm w-100"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <div class="mb-4 w-100">
        <input type="text" id="customerSearch" class="form-control" placeholder="Search by name or email..."
               onkeyup="filterCustomers()" style="max-width:400px;">
    </div>

    <div class="card shadow-sm border-0 w-100 align-self-start" style="height: auto;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Last Active</th>
                            <th>Joined</th>
                            <?php if (is_primary_admin()): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="customersTable">
                        <?php if ($customers): ?>
                            <?php foreach ($customers as $cust): ?>
                                <?php
                                $isInactive    = (int) $cust['months_inactive'] >= $inactivityMonths;
                                $isActive      = (bool) $cust['is_active'];
                                $lastActive    = $cust['last_login_at'] ?? $cust['created_at'];
                                ?>
                                <tr class="customer-row">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($cust['fullname']); ?>&background=10b981&color=fff"
                                                 class="rounded-circle" width="34" height="34" alt="">
                                            <span class="fw-semibold cust-name"><?php echo e($cust['fullname']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-muted cust-email"><?php echo e($cust['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                        </span>
                                        <?php if ($isInactive): ?>
                                            <span class="badge bg-warning text-dark ms-1" title="Eligible for deletion">Dormant</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($cust['order_count']); ?></td>
                                    <td class="fw-bold text-success"><?php echo e(money($cust['total_spent'])); ?></td>
                                    <td class="small text-muted">
                                        <?php echo e($lastActive ? time_ago($lastActive) : 'Never'); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo e(date('M d, Y', strtotime($cust['created_at']))); ?>
                                    </td>
                                    <?php if (is_primary_admin()): ?>
                                        <td class="text-nowrap">
                                            <form method="POST" class="d-inline" action="customers.php">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="customer_id" value="<?php echo e($cust['id']); ?>">
                                                <button type="submit" name="submit_toggle_active" class="btn btn-sm btn-outline-secondary me-1"
                                                    title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                                                    <i class="bi <?php echo $isActive ? 'bi-person-slash' : 'bi-person-check'; ?>"></i>
                                                </button>
                                            </form>
                                            <?php if ($isInactive): ?>
                                                <form method="POST" class="d-inline" action="customers.php"
                                                    onsubmit="return confirm('Permanently delete <?php echo e(addslashes($cust['fullname'])); ?>\'s account? This cannot be undone.');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                    <input type="hidden" name="action" value="delete_customer">
                                                    <input type="hidden" name="customer_id" value="<?php echo e($cust['id']); ?>">
                                                    <button type="submit" name="submit_delete_customer" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                                    No customers registered yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function filterCustomers() {
    const val = document.getElementById('customerSearch').value.toLowerCase();
    document.querySelectorAll('.customer-row').forEach(row => {
        const name  = row.querySelector('.cust-name').textContent.toLowerCase();
        const email = row.querySelector('.cust-email').textContent.toLowerCase();
        row.style.display = (name.includes(val) || email.includes(val)) ? '' : 'none';
    });
}
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>