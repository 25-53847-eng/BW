<?php
require '../db_config.php';

// Drop existing inventory objects if they exist
echo "Cleaning up existing objects...\n";
@$conn->query("DROP VIEW IF EXISTS inventory");
@$conn->query("DROP TRIGGER IF EXISTS po_received_add_inventory");
@$conn->query("DROP TRIGGER IF EXISTS delivery_deduct_inventory");
@$conn->query("DROP TABLE IF EXISTS purchase_orders");
@$conn->query("DROP TABLE IF EXISTS inventory");

echo "Creating new tables and triggers...\n";

// Create inventory table
if($conn->query("
    CREATE TABLE IF NOT EXISTS inventory (
        id INT PRIMARY KEY AUTO_INCREMENT,
        item_code VARCHAR(50) NOT NULL UNIQUE,
        item_name VARCHAR(255) NOT NULL,
        quantity INT DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
")) {
    echo "✓ Inventory table ready\n";
} else {
    echo "✗ Error with inventory table: " . $conn->error . "\n";
}

// Create purchase_orders table
if($conn->query("
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
        INDEX (status),
        INDEX (item_code),
        INDEX (owner_user_id)
    )
")) {
    echo "✓ Purchase orders table ready\n";
} else {
    echo "✗ Error creating purchase_orders table: " . $conn->error . "\n";
}

// Create indexes with IF NOT EXISTS handling
@$conn->query("CREATE INDEX IF NOT EXISTS idx_inventory_item_code ON inventory(item_code)");
@$conn->query("CREATE INDEX IF NOT EXISTS idx_po_item_code ON purchase_orders(item_code)");
@$conn->query("CREATE INDEX IF NOT EXISTS idx_po_status ON purchase_orders(status)");
echo "✓ Indexes ready\n";

// Now create triggers using multi_query
$triggers_sql = "
CREATE TRIGGER po_received_add_inventory
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

CREATE TRIGGER delivery_deduct_inventory
AFTER INSERT ON delivery_records
FOR EACH ROW
BEGIN
    IF NEW.company_name = 'Andison Industrial' THEN
        UPDATE inventory 
        SET quantity = GREATEST(0, quantity - NEW.quantity),
            last_updated = CURRENT_TIMESTAMP
        WHERE item_code = NEW.item_code;
    END IF;
END;
";

if($conn->multi_query($triggers_sql)) {
    do {
        if($result = $conn->store_result()) {
            $result->free();
        }
    } while($conn->next_result());
    echo "✓ Created triggers\n";
} else {
    echo "✗ Error creating triggers: " . $conn->error . "\n";
}

echo "\n✓✓✓ Database setup complete! ✓✓✓\n";
echo "\nInventory system ready to go!\n";
?>



