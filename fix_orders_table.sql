-- SQL script to remove shipping_address column from orders table
-- Run this in SQLite

-- First, create a new table without shipping_address
CREATE TABLE orders_new (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_number TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    total_items INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'placed',
    status_updated_at DATETIME,
    status_updated_by INTEGER,
    phone_number TEXT NOT NULL,
    delivery_notes TEXT,
    delivery_area TEXT,
    delivery_address TEXT NOT NULL,
    landmark TEXT,
    delivery_fee DECIMAL(8,2) DEFAULT 0,
    payment_status TEXT DEFAULT 'pending',
    payment_method TEXT,
    payment_reference TEXT,
    placed_at DATETIME NOT NULL,
    delivered_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (status_updated_by) REFERENCES users(id)
);

-- Copy data from old table to new (excluding shipping_address)
INSERT INTO orders_new (
    id, order_number, user_id, subtotal, tax_amount, total_amount, total_items,
    status, status_updated_at, status_updated_by, phone_number, delivery_notes,
    delivery_area, delivery_address, landmark, delivery_fee,
    payment_status, payment_method, payment_reference,
    placed_at, delivered_at, created_at, updated_at
)
SELECT 
    id, order_number, user_id, subtotal, tax_amount, total_amount, total_items,
    status, status_updated_at, status_updated_by, phone_number, delivery_notes,
    delivery_area, delivery_address, landmark, delivery_fee,
    payment_status, payment_method, payment_reference,
    placed_at, delivered_at, created_at, updated_at
FROM orders;

-- Drop old table and rename new one
DROP TABLE orders;
ALTER TABLE orders_new RENAME TO orders;

-- Recreate indexes
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
