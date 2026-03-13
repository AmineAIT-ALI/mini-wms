-- Mini WMS – Database Schema
-- MySQL 8.0 | utf8mb4 | InnoDB
-- Run: mysql -u root -p < sql/schema.sql

CREATE DATABASE IF NOT EXISTS mini_wms
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mini_wms;

-- ─── USERS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PRODUCTS ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku        VARCHAR(64)  NOT NULL,
    name       VARCHAR(255) NOT NULL,
    stock      INT NOT NULL DEFAULT 0,
    threshold  INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_products_sku (sku),
    CONSTRAINT chk_products_stock     CHECK (stock >= 0),
    CONSTRAINT chk_products_threshold CHECK (threshold >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ORDERS ───────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reference  VARCHAR(64)  NOT NULL,
    status     ENUM('pending', 'picked', 'shipped', 'cancelled') NOT NULL DEFAULT 'pending',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_orders_reference (reference),
    CONSTRAINT fk_orders_created_by FOREIGN KEY (created_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── ORDER ITEMS ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty        INT NOT NULL,
    UNIQUE KEY uq_order_items (order_id, product_id),
    CONSTRAINT chk_order_items_qty CHECK (qty > 0),
    CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders   (id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── STOCK MOVES ──────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock_moves (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    delta      INT NOT NULL,
    reason     ENUM('manual_in', 'manual_out', 'order_pick', 'order_cancel') NOT NULL,
    order_id   INT UNSIGNED NULL DEFAULT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stock_moves_product FOREIGN KEY (product_id) REFERENCES products (id),
    CONSTRAINT fk_stock_moves_order   FOREIGN KEY (order_id)   REFERENCES orders   (id),
    CONSTRAINT fk_stock_moves_user    FOREIGN KEY (created_by) REFERENCES users    (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── AUDIT LOG ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id   INT UNSIGNED NULL DEFAULT NULL,
    action    VARCHAR(64)  NOT NULL,
    entity    VARCHAR(32)  NOT NULL,
    entity_id INT UNSIGNED NULL DEFAULT NULL,
    meta      JSON         NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── INDEXES ──────────────────────────────────────────────────────────────────
CREATE INDEX idx_products_name           ON products   (name);
CREATE INDEX idx_products_stock_threshold ON products  (stock, threshold);
CREATE INDEX idx_stock_moves_product_date ON stock_moves (product_id, created_at);
CREATE INDEX idx_orders_status           ON orders     (status);
CREATE INDEX idx_orders_created_by       ON orders     (created_by);
CREATE INDEX idx_audit_entity            ON audit_log  (entity, entity_id);
CREATE INDEX idx_audit_user_date         ON audit_log  (user_id, created_at);
