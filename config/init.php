<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lagos');

require_once __DIR__ . '/../backend/db.php';

$database = $db ?? $pdo;

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $location): never
{
    header("Location: {$location}");
    exit();
}

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
}

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
        'website_name' => 'AdminHub',
        'site_theme' => 'light',
        'notifications_enabled' => '1',
    ];

    $stmt = $database->prepare(
        "INSERT INTO settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = setting_value"
    );

    foreach ($defaults as $key => $value) {
        $stmt->execute([
            'setting_key' => $key,
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
        'website_name' => 'AdminHub',
        'site_theme' => 'light',
        'notifications_enabled' => '1',
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
        'setting_key' => $key,
        'setting_value' => $value,
    ]);
}

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

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function money(float|int|string|null $value): string
{
    return '$' . number_format((float) $value, 2);
}

$app_settings = load_app_settings($database);
$siteName = app_setting('website_name', 'AdminHub');
$admin_display_name = $_SESSION['admin_name'] ?? 'Admin User';
