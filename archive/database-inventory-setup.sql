-- =====================================================
-- INVENTORY MANAGEMENT SYSTEM SETUP
-- =====================================================

-- 1. CREATE INVENTORY TABLE (Current Stock Tracking)
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(255) NOT NULL,
    quantity INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CREATE PURCHASE_ORDERS TABLE (Track POs from Andison)
CREATE TABLE IF NOT EXISTS purchase_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) UNIQUE,
    item_code VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    quantity_ordered INT NOT NULL,
    quantity_received INT DEFAULT 0,
    status ENUM('pending', 'partial', 'received', 'cancelled') DEFAULT 'pending',
    order_date DATE,
    expected_delivery_date DATE,
    actual_received_date DATE,
    notes TEXT,
    owner_user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_user_id) REFERENCES users(id),
    INDEX (status),
    INDEX (item_code),
    INDEX (owner_user_id)
);

-- 3. TRIGGER: When PO is marked as "received", auto-add to inventory
CREATE TRIGGER IF NOT EXISTS po_received_add_inventory
AFTER UPDATE ON purchase_orders
FOR EACH ROW
BEGIN
    IF NEW.status = 'received' AND OLD.status != 'received' THEN
        INSERT INTO inventory (item_code, item_name, quantity)
        VALUES (NEW.item_code, NEW.item_name, NEW.quantity_received)
        ON DUPLICATE KEY UPDATE 
            quantity = quantity + NEW.quantity_received,
            last_updated = CURRENT_TIMESTAMP;
    END IF;
END;

-- 4. TRIGGER: When delivery_records are inserted, auto-deduct from inventory
CREATE TRIGGER IF NOT EXISTS delivery_deduct_inventory
AFTER INSERT ON delivery_records
FOR EACH ROW
BEGIN
    -- Only deduct if it's a delivery/sale (company_name = 'Andison Industrial')
    IF NEW.company_name = 'Andison Industrial' THEN
        UPDATE inventory 
        SET quantity = GREATEST(0, quantity - NEW.quantity),
            last_updated = CURRENT_TIMESTAMP
        WHERE item_code = NEW.item_code;
    END IF;
END;

-- 5. INDEX for faster queries
CREATE INDEX IF NOT EXISTS idx_inventory_item_code ON inventory(item_code);
CREATE INDEX IF NOT EXISTS idx_po_item_code ON purchase_orders(item_code);
CREATE INDEX IF NOT EXISTS idx_po_status ON purchase_orders(status);
