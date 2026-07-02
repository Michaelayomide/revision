<?php
require_once __DIR__ . '/../config/init.php';
require_admin();
require_role('primary_admin', 'secondary_admin');

$pageTitle = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        flash('danger', 'Security token expired. Please try again.');
        redirect_to('settings.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'save_app_settings') {
        $websiteName = trim($_POST['website_name'] ?? '');
        $siteTheme = ($_POST['site_theme'] ?? 'light') === 'dark' ? 'dark' : 'light';
        $notificationsEnabled = isset($_POST['notifications_enabled']) ? '1' : '0';
        $lowStockThreshold = max(1, (int)($_POST['low_stock_threshold'] ?? 5));

        if ($websiteName === '') {
            flash('danger', 'Website name cannot be empty.');
            redirect_to('settings.php');
        }

        try {
            save_app_setting($database, 'website_name', $websiteName);
            save_app_setting($database, 'site_theme', $siteTheme);
            save_app_setting($database, 'notifications_enabled', $notificationsEnabled);
            save_app_setting($database, 'low_stock_threshold', (string)$lowStockThreshold);

            // Security configurations (Only Super Admin / primary_admin is allowed to change)
            if (is_primary_admin()) {
                $customerPortalEnabled = isset($_POST['customer_portal_enabled']) ? '1' : '0';
                $inactivityMonths = max(1, (int)($_POST['inactivity_months'] ?? 12));

                save_app_setting($database, 'customer_portal_enabled', $customerPortalEnabled);
                save_app_setting($database, 'inactivity_months', (string)$inactivityMonths);
            }

            flash('success', 'Application settings saved.');
        } catch (PDOException $e) {
            flash('danger', 'Settings could not be saved.');
        }

        redirect_to('settings.php');
    }
}

$currentAdmin = [
    'fullname' => $_SESSION['admin_name'] ?? 'Admin User',
    'email' => '',
];

try {
    $stmt = $database->prepare('SELECT fullname, email FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int) ($_SESSION['admin_id'] ?? 0)]);
    $admin = $stmt->fetch();
    if ($admin) {
        $currentAdmin = $admin;
        $admin_display_name = $admin['fullname'];
    }
} catch (PDOException $e) {
    // The settings screen can still render app-level controls if admin lookup fails.
}

$flashMessages = consume_flash_messages();

include __DIR__ . '/../components/header.php';
include __DIR__ . '/../components/navbar.php';
include __DIR__ . '/../components/sidebar.php';
?>

<main class="main-content">
    <h1 class="fw-bold text-primary mb-4">Settings</h1>

    <?php foreach ($flashMessages as $message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show shadow-sm">
            <?php echo e($message['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white fw-bold">Profile</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentAdmin['fullname']); ?>&background=6366f1&color=fff" class="rounded-circle" width="64" height="64" alt="Avatar">
                        <div>
                            <div class="fw-semibold"><?php echo e($currentAdmin['fullname']); ?></div>
                            <div class="small text-muted"><?php echo e($currentAdmin['email']); ?></div>
                        </div>
                    </div>
                    <p class="small text-muted mb-0">Profile identity is used in the shared navbar. Account password changes can remain in your existing security flow.</p>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white fw-bold">Current Configuration</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Website</span>
                        <strong><?php echo e(app_setting('website_name', 'AdminHub')); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Theme</span>
                        <strong><?php echo e(ucfirst(app_setting('site_theme', 'light'))); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Notifications</span>
                        <strong><?php echo app_setting('notifications_enabled', '1') === '1' ? 'On' : 'Off'; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Stock Threshold</span>
                        <strong><?php echo e(app_setting('low_stock_threshold', '5')); ?> items</strong>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>Customer Portal</span>
                        <strong><?php echo app_setting('customer_portal_enabled', '1') === '1' ? 'Enabled' : 'Disabled'; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Dormancy Period</span>
                        <strong><?php echo e(app_setting('inactivity_months', '12')); ?> months</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <form method="POST" class="card shadow-sm border-0">
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="action" value="save_app_settings">
                <div class="card-header bg-white fw-bold">Application Settings</div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="websiteName">Website Name</label>
                        <input
                            type="text"
                            class="form-control"
                            id="websiteName"
                            name="website_name"
                            value="<?php echo e(app_setting('website_name', 'AdminHub')); ?>"
                            required>
                        <div class="form-text">This name appears in the browser title, navbar, sidebar, and footer.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Theme</label>
                        <div class="d-flex gap-3 flex-wrap">
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="site_theme" value="light" <?php echo app_setting('site_theme', 'light') !== 'dark' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Light</span>
                            </label>
                            <label class="form-check">
                                <input class="form-check-input" type="radio" name="site_theme" value="dark" <?php echo app_setting('site_theme', 'light') === 'dark' ? 'checked' : ''; ?>>
                                <span class="form-check-label">Dark</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notification Preferences</label>
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="notificationsEnabled"
                                name="notifications_enabled"
                                <?php echo app_setting('notifications_enabled', '1') === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notificationsEnabled">Show notification icons and panel widgets</label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold" for="lowStockThreshold">Low Stock Threshold</label>
                        <input
                            type="number"
                            class="form-control"
                            id="lowStockThreshold"
                            name="low_stock_threshold"
                            value="<?php echo e(app_setting('low_stock_threshold', '5')); ?>"
                            min="1"
                            required>
                        <div class="form-text">Alert threshold for product stock quantities.</div>
                    </div>

                    <hr>

                    <div class="mb-4">
                        <h5 class="fw-bold text-secondary mb-3">Security & System Configurations</h5>
                        <?php if (!is_primary_admin()): ?>
                            <div class="alert alert-warning py-2 mb-3 small">
                                <i class="bi bi-shield-lock-fill me-2"></i> Security and system configurations are locked. Only Super Admins can alter these parameters.
                            </div>
                        <?php endif; ?>

                        <div class="form-check form-switch mb-3">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="customerPortalEnabled"
                                name="customer_portal_enabled"
                                <?php echo app_setting('customer_portal_enabled', '1') === '1' ? 'checked' : ''; ?>
                                <?php echo !is_primary_admin() ? 'disabled' : ''; ?>>
                            <label class="form-check-label" for="customerPortalEnabled">Enable Customer Self-Registration & Portal</label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="inactivityMonths">Dormant Customer Account Deletion (Months)</label>
                            <input
                                type="number"
                                class="form-control"
                                id="inactivityMonths"
                                name="inactivity_months"
                                value="<?php echo e(app_setting('inactivity_months', '12')); ?>"
                                min="1"
                                <?php echo !is_primary_admin() ? 'readonly' : ''; ?>
                                required>
                            <div class="form-text">Inactivity duration before a customer account becomes eligible for deletion.</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white text-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-save me-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../components/footer.php'; ?>
