<?php
require_once __DIR__ . '/../db_config.php';

// Add inventory_status column if it doesn't exist
$sql = "ALTER TABLE delivery_records ADD COLUMN inventory_status VARCHAR(50) DEFAULT NULL COMMENT 'Inventory classification: INVENTORY, Stock in Manila, or NULL for sales'";

if ($conn->query($sql)) {
    echo "✅ Added inventory_status column to delivery_records table\n";
} else {
    // Column might already exist - that's fine
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "✅ Column inventory_status already exists\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
}

// Verify the column exists
$result = $conn->query("DESCRIBE delivery_records inventory_status");
if ($result && $result->num_rows > 0) {
    echo "✅ Column verified\n";
} else {
    echo "⚠️ Column not found\n";
}
?>
