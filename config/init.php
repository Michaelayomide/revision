<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lagos');

require_once __DIR__ . '/../backend/db.php';

$database = $db ?? $pdo;

// ─── Output escaping ─────────────────────────────────────────────────────────

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ─── Redirects ────────────────────────────────────────────────────────────────

function redirect_to(string $location): never
{
    header("Location: {$location}");
    exit();
}

// ─── Admin authentication & RBAC ─────────────────────────────────────────────

function require_admin(): void
{
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $_SESSION['auth_errors'] = ['Access denied. Please sign in to continue.'];
        redirect_to('login.php');
    }

    $maxIdleSeconds = 900;
    if (isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $maxIdleSeconds) {
        session_unset();
        session_destroy();
        redirect_to('login.php?reason=timeout');
    }

    $_SESSION['last_activity'] = time();

    // Dynamically query database for role and status
    global $database;
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) {
        session_unset();
        session_destroy();
        redirect_to('login.php');
    }

    try {
        $stmt = $database->prepare("SELECT role, status FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $adminId]);
        $admin = $stmt->fetch();

        if (!$admin && !($adminId === 1 || $adminId === 2)) {
            session_unset();
            session_destroy();
            $_SESSION['auth_errors'] = ['Your account has been deleted.'];
            redirect_to('login.php');
        }

        $role = $admin['role'] ?? 'secondary_admin';
        $status = $admin['status'] ?? 'Active';

        if ($adminId === 1 || $adminId === 2) {
            $role = 'primary_admin';
            $status = 'Active';
        }

        if ($status !== 'Active') {
            session_unset();
            session_destroy();
            $_SESSION['auth_errors'] = ['Your account has been suspended.'];
            redirect_to('login.php');
        }

        $_SESSION['admin_role'] = $role;
    } catch (PDOException $e) {
        // Fallback to session if database lookup fails
        if ($adminId === 1 || $adminId === 2) {
            $_SESSION['admin_role'] = 'primary_admin';
        }
    }
}

/**
 * Require the current admin to have one of the specified roles.
 * Call AFTER require_admin().
 *
 * @param string ...$roles  e.g. 'primary_admin', 'secondary_admin', 'editor'
 */
function require_role(string ...$roles): void
{
    $currentRole = current_admin_role();
    if (!in_array($currentRole, $roles, true)) {
        flash('danger', 'You do not have permission to access that area.');
        redirect_to('index.php');
    }
}

/** Return the current admin's role string from session/database. */
function current_admin_role(): string
{
    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId === 1 || $adminId === 2) {
        return 'primary_admin';
    }
    return $_SESSION['admin_role'] ?? '';
}

function is_primary_admin(): bool
{
    return current_admin_role() === 'primary_admin';
}

function is_secondary_admin(): bool
{
    return current_admin_role() === 'secondary_admin';
}

function is_editor(): bool
{
    return current_admin_role() === 'editor';
}

// ─── Customer authentication ──────────────────────────────────────────────────

/**
 * Require the visitor to be logged in as a customer.
 * Redirects to the customer login page if not.
 */
function require_customer(string $redirectPath = '../customer/login.php'): void
{
    if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
        redirect_to($redirectPath);
    }

    $maxIdleSeconds = 1800; // 30 minutes
    if (isset($_SESSION['customer_last_activity']) && (time() - (int) $_SESSION['customer_last_activity']) > $maxIdleSeconds) {
        session_unset();
        session_destroy();
        redirect_to($redirectPath . '?reason=timeout');
    }

    $_SESSION['customer_last_activity'] = time();
}

// ─── Settings ────────────────────────────────────────────────────────────────

function ensure_settings_table(PDO $database): void
{
    $database->exec(
        "CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $defaults = [
        'website_name'             => 'AdminHub',
        'site_theme'               => 'light',
        'notifications_enabled'    => '1',
        'customer_portal_enabled'  => '1',
        'inactivity_months'        => '12',
        'low_stock_threshold'      => '5',
    ];

    $stmt = $database->prepare(
        "INSERT INTO settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = setting_value"
    );

    foreach ($defaults as $key => $value) {
        $stmt->execute([
            'setting_key'   => $key,
            'setting_value' => $value,
        ]);
    }
}

function load_app_settings(PDO $database): array
{
    try {
        ensure_settings_table($database);
        $stmt = $database->prepare('SELECT setting_key, setting_value FROM settings');
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException) {
        $settings = [];
    }

    return array_merge([
        'website_name'            => 'AdminHub',
        'site_theme'              => 'light',
        'notifications_enabled'   => '1',
        'customer_portal_enabled' => '1',
        'inactivity_months'       => '12',
        'low_stock_threshold'     => '5',
    ], $settings);
}

function app_setting(string $key, string $default = ''): string
{
    global $app_settings;

    return (string) ($app_settings[$key] ?? $default);
}

function save_app_setting(PDO $database, string $key, string $value): void
{
    $stmt = $database->prepare(
        "INSERT INTO settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->execute([
        'setting_key'   => $key,
        'setting_value' => $value,
    ]);
}

// ─── CSRF ────────────────────────────────────────────────────────────────────

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Flash messages ───────────────────────────────────────────────────────────

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

function consume_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

// ─── Notifications ────────────────────────────────────────────────────────────

/**
 * Send a notification to all admins (primary + secondary + editors).
 */
function notify_admins(PDO $database, string $type, string $message, array $extraData = []): void
{
    try {
        $stmt = $database->prepare('SELECT id FROM admins');
        $stmt->execute();
        $adminIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $ins = $database->prepare(
            "INSERT INTO notifications (recipient_type, recipient_id, type, message, extra_data)
             VALUES ('admin', :rid, :type, :message, :data)"
        );

        $encodedData = $extraData ? json_encode($extraData) : null;

        foreach ($adminIds as $adminId) {
            $ins->execute([
                'rid'     => $adminId,
                'type'    => $type,
                'message' => $message,
                'data'    => $encodedData,
            ]);
        }
    } catch (PDOException) {
        // Notifications are non-critical; fail silently
    }
}

/**
 * Send a notification to a specific customer.
 */
function notify_customer(PDO $database, int $customerId, string $type, string $message, array $extraData = []): void
{
    try {
        $stmt = $database->prepare(
            "INSERT INTO notifications (recipient_type, recipient_id, type, message, extra_data)
             VALUES ('customer', :rid, :type, :message, :data)"
        );
        $stmt->execute([
            'rid'     => $customerId,
            'type'    => $type,
            'message' => $message,
            'data'    => $extraData ? json_encode($extraData) : null,
        ]);
    } catch (PDOException) {
        // Non-critical
    }
}

/**
 * Count unread notifications for a specific admin.
 */
function unread_admin_notification_count(PDO $database, int $adminId): int
{
    try {
        $stmt = $database->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE recipient_type = 'admin' AND recipient_id = :id AND is_read = 0"
        );
        $stmt->execute(['id' => $adminId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

/**
 * Count unread notifications for a specific customer.
 */
function unread_customer_notification_count(PDO $database, int $customerId): int
{
    try {
        $stmt = $database->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE recipient_type = 'customer' AND recipient_id = :id AND is_read = 0"
        );
        $stmt->execute(['id' => $customerId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException) {
        return 0;
    }
}

// ─── Formatting helpers ───────────────────────────────────────────────────────

function money(float|int|string|null $value): string
{
    return '$' . number_format((float) $value, 2);
}

function time_ago(string $datetime): string
{
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->days === 0) {
        if ($diff->h === 0) {
            return $diff->i <= 1 ? 'just now' : "{$diff->i}m ago";
        }
        return "{$diff->h}h ago";
    }
    if ($diff->days < 7) {
        return "{$diff->days}d ago";
    }
    return $then->format('M j, Y');
}

// ─── Boot ─────────────────────────────────────────────────────────────────────

$app_settings        = load_app_settings($database);
$siteName            = app_setting('website_name', 'AdminHub');
$admin_display_name  = $_SESSION['admin_name'] ?? 'Admin User';
$admin_id            = (int) ($_SESSION['admin_id'] ?? 0);
$unread_notif_count  = 0;

if ($admin_id > 0) {
    $unread_notif_count = unread_admin_notification_count($database, $admin_id);
}
