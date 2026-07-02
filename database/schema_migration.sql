-- ============================================================
-- AdminHub Schema Migration v2.0
-- Run once against the `revsion` database
-- ============================================================

-- 1. Extend admins table: add role, reset_token, token_expire, created_at
ALTER TABLE admins
    ADD COLUMN IF NOT EXISTS role ENUM('primary_admin','secondary_admin','editor') NOT NULL DEFAULT 'secondary_admin' AFTER email,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(100) NULL AFTER password,
    ADD COLUMN IF NOT EXISTS token_expire DATETIME NULL AFTER reset_token,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER token_expire;

-- Make the very first registered admin the primary_admin
UPDATE admins SET role = 'primary_admin' WHERE id = (SELECT MIN(id) FROM (SELECT id FROM admins) AS t);

-- 2. Create customers table (self-registered via portal)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(160) NOT NULL,
    email VARCHAR(200) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_customers_email UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create cart_items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT uq_cart_item UNIQUE (customer_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('admin','customer') NOT NULL,
    recipient_id INT NOT NULL,
    type VARCHAR(80) NOT NULL DEFAULT 'general',
    message TEXT NOT NULL,
    extra_data JSON NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_recipient (recipient_type, recipient_id, is_read),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Extend orders table
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER customer_id,
    ADD COLUMN IF NOT EXISTS tracking_url VARCHAR(500) NULL AFTER shipping_status,
    ADD COLUMN IF NOT EXISTS approved_at TIMESTAMP NULL AFTER tracking_url;

-- Add foreign keys to orders (IF NOT EXISTS guard via named constraints)
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_orders_approver FOREIGN KEY (approved_by) REFERENCES admins(id) ON DELETE SET NULL;

-- 6. Extend order_items: add product_name snapshot (survives product deletion)
ALTER TABLE order_items
    ADD COLUMN IF NOT EXISTS product_name VARCHAR(160) NOT NULL DEFAULT '' AFTER product_id;

-- 7. Seed updated settings defaults
INSERT INTO settings (setting_key, setting_value) VALUES
    ('customer_portal_enabled', '1'),
    ('inactivity_months', '12'),
    ('low_stock_threshold', '5')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
