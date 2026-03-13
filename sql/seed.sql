-- Mini WMS – Seed Data
-- Run AFTER schema.sql: mysql -u root -p mini_wms < sql/seed.sql
-- (database is selected via the CLI argument, no USE needed)

-- ─── USERS ────────────────────────────────────────────────────────────────────
-- Passwords: Password123! (bcrypt cost=12)
-- To regenerate: php -r "echo password_hash('Password123!', PASSWORD_BCRYPT, ['cost'=>12]);"
INSERT INTO users (email, password_hash, role) VALUES
('admin@local.test', '$2y$12$F5wtT3Aa3XxlZwQoFY1tzOxq5VDxfsgOSLqlbIdE.3y86NBf8vWvW', 'admin'),
('user@local.test',  '$2y$12$F5wtT3Aa3XxlZwQoFY1tzOxq5VDxfsgOSLqlbIdE.3y86NBf8vWvW', 'user');

-- ─── PRODUCTS ─────────────────────────────────────────────────────────────────
INSERT INTO products (sku, name, stock, threshold) VALUES
('SKU-001', 'Wireless Keyboard',      45,  10),
('SKU-002', 'USB-C Hub 7-Port',        8,  10),
('SKU-003', 'Monitor Stand Adjustable',3,   5),
('SKU-004', 'Mechanical Keyboard RGB', 22,  5),
('SKU-005', 'Webcam HD 1080p',         0,   5),
('SKU-006', 'Noise-Cancelling Headset',15,  8),
('SKU-007', 'Laptop Cooling Pad',      4,   5),
('SKU-008', 'HDMI Cable 2m',           60, 10);

-- ─── ORDERS ───────────────────────────────────────────────────────────────────
INSERT INTO orders (reference, status, created_by) VALUES
('ORD-2024-001', 'pending', 1),
('ORD-2024-002', 'pending', 2),
('ORD-2024-003', 'pending', 1);

-- ─── ORDER ITEMS ──────────────────────────────────────────────────────────────
-- ORD-2024-001
INSERT INTO order_items (order_id, product_id, qty) VALUES
(1, 1, 5),
(1, 4, 2);

-- ORD-2024-002
INSERT INTO order_items (order_id, product_id, qty) VALUES
(2, 6, 3),
(2, 8, 10);

-- ORD-2024-003
INSERT INTO order_items (order_id, product_id, qty) VALUES
(3, 2, 4),
(3, 3, 1),
(3, 7, 2);

-- ─── AUDIT LOG ────────────────────────────────────────────────────────────────
INSERT INTO audit_log (user_id, action, entity, entity_id, meta) VALUES
(1, 'product_create', 'product', 1, '{"sku":"SKU-001","name":"Wireless Keyboard"}'),
(1, 'order_create',   'order',   1, '{"reference":"ORD-2024-001"}'),
(2, 'order_create',   'order',   2, '{"reference":"ORD-2024-002"}'),
(1, 'order_create',   'order',   3, '{"reference":"ORD-2024-003"}');
