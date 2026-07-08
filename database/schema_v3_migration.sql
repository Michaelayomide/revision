-- ============================================================
-- AdminHub Schema Migration v3.0
-- Run once against the `revsion` database
-- Fixes order columns and adds shipping details to orders
-- ============================================================

-- 1. Add missing columns to orders table
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS shipping_phone   VARCHAR(30)  NULL AFTER customer_id,
    ADD COLUMN IF NOT EXISTS shipping_address TEXT         NULL AFTER shipping_phone,
    ADD COLUMN IF NOT EXISTS payment_method   VARCHAR(60)  NOT NULL DEFAULT 'Cash on Delivery' AFTER shipping_address;

-- 2. Add customer_name snapshot to orders (denormalised for robustness)
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS customer_name VARCHAR(160) NOT NULL DEFAULT '' AFTER shipping_address;

-- 3. Ensure order_items uses unit_price (already in schema — guard only)
-- Nothing to add, unit_price already exists.

-- 4. Ensure products has description, category, image_path (already added by admin v2 — guard only)
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS description TEXT         NULL AFTER name,
    ADD COLUMN IF NOT EXISTS category    VARCHAR(80)  NOT NULL DEFAULT 'General' AFTER description,
    ADD COLUMN IF NOT EXISTS image_path  VARCHAR(500) NULL AFTER category;

-- 5. Add phone and address columns to customers for checkout pre-fill (optional profile data)
ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS phone_number        VARCHAR(30)  NULL AFTER email,
    ADD COLUMN IF NOT EXISTS residential_address VARCHAR(255) NULL AFTER phone_number,
    ADD COLUMN IF NOT EXISTS city                VARCHAR(100) NULL AFTER residential_address,
    ADD COLUMN IF NOT EXISTS state_province      VARCHAR(100) NULL AFTER city,
    ADD COLUMN IF NOT EXISTS country             VARCHAR(100) NULL DEFAULT 'Nigeria' AFTER state_province;

-- 6. Add order idempotency token column to orders (prevents duplicate submission)
ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(64) NULL UNIQUE AFTER payment_method;
