<?php
require_once __DIR__ . '/../db_config.php';

$sql = "ALTER TABLE delivery_records ADD COLUMN record_type VARCHAR(20) DEFAULT 'inventory' AFTER dataset_name;";

if ($conn->query($sql)) {
    echo "✓ Column 'record_type' added successfully!\n";
} else {
    // Check if column already exists
    if (strpos($conn->error, "Duplicate column name") !== false) {
        echo "✓ Column 'record_type' already exists!\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
    }
}

$conn->close();
?>
