<?php
$dbConfigPath = dirname(__DIR__) . '/db_config.php';
require_once $dbConfigPath;

// Check and add peso_cost column if it doesn't exist
$checkPesoCost = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'peso_cost'");
if ($checkPesoCost->num_rows === 0) {
    echo "Adding peso_cost column...\n";
    if ($conn->query("ALTER TABLE delivery_records ADD COLUMN peso_cost DECIMAL(15,2) DEFAULT 0 AFTER po_number")) {
        echo "✓ peso_cost column added\n";
    } else {
        echo "✗ Failed to add peso_cost: " . $conn->error . "\n";
    }
} else {
    echo "✓ peso_cost column already exists\n";
}

// Check and add foreign_cost column if it doesn't exist
$checkForeignCost = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'foreign_cost'");
if ($checkForeignCost->num_rows === 0) {
    echo "Adding foreign_cost column...\n";
    if ($conn->query("ALTER TABLE delivery_records ADD COLUMN foreign_cost DECIMAL(15,2) DEFAULT 0 AFTER peso_cost")) {
        echo "✓ foreign_cost column added\n";
    } else {
        echo "✗ Failed to add foreign_cost: " . $conn->error . "\n";
    }
} else {
    echo "✓ foreign_cost column already exists\n";
}

echo "\nDatabase schema updated successfully!\n";
?>
