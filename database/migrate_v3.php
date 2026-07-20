<?php
/**
 * Schema Migration v3 — Run this file once via browser: /database/migrate_v3.php
 * Adds missing columns needed by the customer checkout flow.
 */
declare(strict_types=1);
require_once __DIR__ . '/../backend/db.php';
$database = $db ?? $pdo;

$migrations = [];
$errors = [];

function safe_alter(PDO $db, string $sql, string $label, array &$migrations, array &$errors): void
{
    try {
        $db->exec($sql);
        $migrations[] = "✅ $label";
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), 'Duplicate column')) {
            $migrations[] = "⏭️  $label (already exists — skipped)";
        } else {
            $errors[] = "❌ $label: " . $e->getMessage();
        }
    }
}

// ── orders table ──────────────────────────────────────────────────────────────
safe_alter($database,
    "ALTER TABLE orders ADD COLUMN shipping_phone VARCHAR(30) NULL AFTER customer_id",
    "orders.shipping_phone", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE orders ADD COLUMN shipping_address TEXT NULL AFTER shipping_phone",
    "orders.shipping_address", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE orders ADD COLUMN payment_method VARCHAR(60) NOT NULL DEFAULT 'Cash on Delivery' AFTER shipping_address",
    "orders.payment_method", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE orders ADD COLUMN customer_name VARCHAR(160) NOT NULL DEFAULT '' AFTER payment_method",
    "orders.customer_name", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE orders ADD COLUMN idempotency_key VARCHAR(64) NULL AFTER customer_name",
    "orders.idempotency_key", $migrations, $errors);

// Add unique index on idempotency_key (separate from column add to handle errors independently)
try {
    $database->exec("ALTER TABLE orders ADD UNIQUE INDEX uq_idempotency_key (idempotency_key)");
    $migrations[] = "✅ orders.idempotency_key unique index";
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'Duplicate key') || str_contains($e->getMessage(), 'already exists')) {
        $migrations[] = "⏭️  orders.idempotency_key unique index (already exists — skipped)";
    } else {
        $errors[] = "❌ orders.idempotency_key unique index: " . $e->getMessage();
    }
}

// ── products table ────────────────────────────────────────────────────────────
safe_alter($database,
    "ALTER TABLE products ADD COLUMN description TEXT NULL AFTER name",
    "products.description", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE products ADD COLUMN category VARCHAR(80) NOT NULL DEFAULT 'General' AFTER description",
    "products.category", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE products ADD COLUMN image_path VARCHAR(500) NULL AFTER category",
    "products.image_path", $migrations, $errors);

// ── customers table ───────────────────────────────────────────────────────────
safe_alter($database,
    "ALTER TABLE customers ADD COLUMN phone_number VARCHAR(30) NULL AFTER email",
    "customers.phone_number", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE customers ADD COLUMN residential_address VARCHAR(255) NULL AFTER phone_number",
    "customers.residential_address", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE customers ADD COLUMN city VARCHAR(100) NULL AFTER residential_address",
    "customers.city", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE customers ADD COLUMN state_province VARCHAR(100) NULL AFTER city",
    "customers.state_province", $migrations, $errors);

safe_alter($database,
    "ALTER TABLE customers ADD COLUMN country VARCHAR(100) NULL DEFAULT 'Nigeria' AFTER state_province",
    "customers.country", $migrations, $errors);

// ── order_items: add product_name snapshot ─────────────────────────────────────
safe_alter($database,
    "ALTER TABLE order_items ADD COLUMN product_name VARCHAR(160) NOT NULL DEFAULT '' AFTER product_id",
    "order_items.product_name", $migrations, $errors);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schema Migration v3</title>
    <style>
        body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 30px; }
        h1 { color: #10b981; }
        ul { line-height: 2; }
        .err { color: #ef4444; }
        .ok  { color: #10b981; }
    </style>
</head>
<body>
<h1>Schema Migration v3 — Results</h1>
<ul>
<?php foreach ($migrations as $m): ?>
    <li class="<?= str_starts_with($m, '❌') ? 'err' : 'ok' ?>"><?= htmlspecialchars($m) ?></li>
<?php endforeach; ?>
<?php foreach ($errors as $err): ?>
    <li class="err"><?= htmlspecialchars($err) ?></li>
<?php endforeach; ?>
</ul>
<?php if (empty($errors)): ?>
    <p style="color:#10b981; font-size:1.2rem;">✅ Migration complete — no errors.</p>
<?php else: ?>
    <p style="color:#ef4444; font-size:1.2rem;">⚠️ Migration finished with <?= count($errors) ?> error(s). Review above.</p>
<?php endif; ?>
</body>
</html>
