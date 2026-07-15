<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/init.php';
require_customer('../customer/login.php');

$pageTitle  = 'My Profile';
$customerId = (int) $_SESSION['customer_id'];

$stmt = $database->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $customerId]);
$customer = $stmt->fetch();

if (!$customer) {
    flash('error', 'Account not found.');
    redirect_to('login.php');
}

$errors = [];
$flashMessages = consume_flash_messages();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Security token invalid. Please try again.';
    }

    $fullname       = trim($_POST['fullname'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone_number'] ?? '');
    $address        = trim($_POST['residential_address'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state_province'] ?? '');
    $country        = trim($_POST['country'] ?? '');
    $postal         = trim($_POST['postal_code'] ?? '');

    $currentPassword     = $_POST['current_password'] ?? '';
    $newPassword         = $_POST['new_password'] ?? '';
    $confirmNewPassword  = $_POST['confirm_new_password'] ?? '';

    if ($fullname === '' || $email === '' || $phone === '') {
        $errors[] = 'Full name, email, and phone number are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email format.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
        $errors[] = 'Please enter a valid phone number.';
    }
    if (strlen($address) > 0 && strlen($address) < 5) {
        $errors[] = 'Residential address must be at least 5 characters if provided.';
    }

    if ($currentPassword !== '' || $newPassword !== '' || $confirmNewPassword !== '') {
        if ($currentPassword === '') {
            $errors[] = 'Please enter your current password to change it.';
        } elseif (!password_verify($currentPassword, $customer['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if ($newPassword === '') {
            $errors[] = 'Please enter a new password.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        }
        if ($newPassword !== $confirmNewPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (empty($errors)) {
        try {
            $update = $database->prepare(
                "UPDATE customers
                 SET fullname = :fullname,
                     email = :email,
                     phone_number = :phone,
                     residential_address = :address,
                     city = :city,
                     state_province = :state,
                     country = :country,
                     postal_code = :postal
                 WHERE id = :id"
            );
            $update->execute([
                'fullname'       => $fullname,
                'email'          => $email,
                'phone'          => $phone,
                'address'        => $address !== '' ? $address : null,
                'city'           => $city !== '' ? $city : null,
                'state'          => $state !== '' ? $state : null,
                'country'        => $country !== '' ? $country : null,
                'postal'         => $postal !== '' ? $postal : null,
                'id'             => $customerId,
            ]);

            if ($newPassword !== '') {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $database->prepare("UPDATE customers SET password_hash = :hash WHERE id = :id")
                         ->execute(['hash' => $newHash, 'id' => $customerId]);
            }

            $_SESSION['customer_name'] = $fullname;

            flash('success', 'Profile updated successfully!');
            redirect_to('profile.php');
        } catch (PDOException $e) {
            $errors[] = 'Database update failed. Please try again.';
        }
    }
}

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/navbar.php';
?>

<div class="customer-main">
    <!-- Page Header -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb" style="font-size:0.82rem;">
                <li class="breadcrumb-item"><a href="index.php" class="text-link-green">Dashboard</a></li>
                <li class="breadcrumb-item active">My Profile</li>
            </ol>
        </nav>
        <h2 class="fw-bold mb-0">My Profile</h2>
        <p class="text-muted mb-0">Manage your account information and preferences.</p>
    </div>

    <!-- Flash Messages -->
    <?php foreach ($flashMessages as $msg): ?>
        <div class="alert alert-<?= e($msg['type']) ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi <?= e($msg['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle') ?> me-2"></i>
            <?= e($msg['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?= e($err) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <div class="row g-3 g-lg-4">
        <!-- Left: Personal Information -->
        <div class="col-12 col-lg-7">
            <form method="POST" action="profile.php" novalidate id="profile-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="stat-card mb-3 mb-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3 mb-lg-4">
                        <div class="stat-icon" style="background:rgba(16,185,129,0.12);">
                            <i class="bi bi-person-fill" style="color:var(--brand-primary);"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Personal Information</h5>
                    </div>

                    <div class="row g-2 g-md-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="fullname">Full Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" id="fullname" name="fullname" class="form-control"
                                       placeholder="Your full name" required
                                       value="<?= e($customer['fullname'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="email">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email" class="form-control"
                                       placeholder="you@example.com" required
                                       value="<?= e($customer['email'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="phone_number">Phone Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="phone_number" name="phone_number" class="form-control"
                                       placeholder="+234 801 234 5678" required
                                       value="<?= e($customer['phone_number'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="residential_address">Residential Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" id="residential_address" name="residential_address" class="form-control"
                                       placeholder="Street address"
                                       value="<?= e($customer['residential_address'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="city">City</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-building"></i></span>
                                <input type="text" id="city" name="city" class="form-control"
                                       placeholder="City"
                                       value="<?= e($customer['city'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="state_province">State / Province</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-map"></i></span>
                                <input type="text" id="state_province" name="state_province" class="form-control"
                                       placeholder="State or Province"
                                       value="<?= e($customer['state_province'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="country">Country</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-globe"></i></span>
                                <input type="text" id="country" name="country" class="form-control"
                                       placeholder="Country"
                                       value="<?= e($customer['country'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold" for="postal_code">Postal / ZIP Code</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                <input type="text" id="postal_code" name="postal_code" class="form-control"
                                       placeholder="Postal code"
                                       value="<?= e($customer['postal_code'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="stat-card mb-3 mb-lg-4">
                    <div class="d-flex align-items-center gap-2 mb-3 mb-lg-4">
                        <div class="stat-icon" style="background:rgba(245,158,11,0.12);">
                            <i class="bi bi-shield-lock-fill" style="color:#f59e0b;"></i>
                        </div>
                        <h5 class="fw-bold mb-0">Change Password</h5>
                    </div>
                    <p class="text-muted mb-3 mb-lg-4" style="font-size:0.88rem;">Leave blank to keep your current password.</p>

                    <div class="row g-2 g-md-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="current_password">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" id="current_password" name="current_password" class="form-control"
                                       placeholder="Current password" autocomplete="current-password">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="new_password">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" id="new_password" name="new_password" class="form-control"
                                       placeholder="New password" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold" for="confirm_new_password">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-shield-check"></i></span>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control"
                                       placeholder="Repeat new password" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2 gap-sm-3">
                    <button type="submit" class="btn btn-primary-green px-5 py-2 fw-bold">
                        <i class="bi bi-check-lg me-2"></i> Save Changes
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary px-5 py-2 fw-semibold">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Right: Account Overview -->
        <div class="col-12 col-lg-5">
            <div class="stat-card mb-3 mb-lg-4 d-lg-block" style="position:static;">
                <div class="text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($customer['fullname'] ?? 'Customer') ?>&background=10b981&color=fff&size=128"
                         alt="Avatar"
                         class="rounded-circle mb-3"
                         style="width:80px; height:80px; box-shadow:0 4px 12px rgba(0,0,0,0.12);">
                    <h4 class="fw-bold mb-1"><?= e($customer['fullname'] ?? 'Customer') ?></h4>
                    <p class="text-muted mb-0" style="font-size:0.88rem;"><?= e($customer['email'] ?? '') ?></p>
                </div>

                <hr style="border-color: var(--border);">

                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:rgba(16,185,129,0.12); width:40px; height:40px; font-size:1.1rem; margin-bottom:0; flex-shrink:0;">
                        <i class="bi bi-calendar3" style="color:var(--brand-primary);"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.8px;">Member Since</div>
                        <div class="fw-semibold" style="font-size:0.92rem;"><?= e(date('F j, Y', strtotime($customer['created_at'] ?? 'now'))) ?></div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:rgba(59,130,246,0.12); width:40px; height:40px; font-size:1.1rem; margin-bottom:0; flex-shrink:0;">
                        <i class="bi bi-telephone" style="color:#3b82f6;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.8px;">Phone</div>
                        <div class="fw-semibold" style="font-size:0.92rem;"><?= e($customer['phone_number'] ?? 'Not set') ?></div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.12); width:40px; height:40px; font-size:1.1rem; margin-bottom:0; flex-shrink:0;">
                        <i class="bi bi-geo-alt" style="color:#f59e0b;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.8px;">Location</div>
                        <div class="fw-semibold" style="font-size:0.92rem;">
                            <?php
                            $locParts = array_filter([$customer['city'] ?? '', $customer['country'] ?? '']);
                            echo e(implode(', ', $locParts) ?: 'Not set');
                            ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:rgba(239,68,68,0.12); width:40px; height:40px; font-size:1.1rem; margin-bottom:0; flex-shrink:0;">
                        <i class="bi bi-person-check" style="color:#ef4444;"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.8px;">Account Status</div>
                        <div class="fw-semibold" style="font-size:0.92rem; color:var(--brand-primary);">
                            <?= ((int) ($customer['is_active'] ?? 1)) ? 'Active' : 'Inactive' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card" style="border:1px solid rgba(239,68,68,0.2); background:rgba(239,68,68,0.03);">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-info-circle-fill" style="color:#ef4444; font-size:1.1rem;"></i>
                    <h6 class="fw-bold mb-0" style="color:#ef4444;">Need Help?</h6>
                </div>
                <p class="mb-0" style="font-size:0.85rem; color:var(--text-secondary);">
                    Contact our support team if you need assistance with your account.
                    <a href="mailto:support@example.com" class="text-link-green fw-semibold">support@example.com</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = <<<'JS'
<script>
document.getElementById('profile-form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('was-validated');
        return;
    }
});
</script>
JS;
require_once __DIR__ . '/components/footer.php';
?>
