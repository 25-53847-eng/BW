<?php
require 'db_config.php';

// Add owner_user_id column to warranty_replacements if it doesn't exist
$check = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='warranty_replacements' AND COLUMN_NAME='owner_user_id'");

if ($check && $check->num_rows === 0) {
    // Column doesn't exist, add it
    $alter_sql = "ALTER TABLE warranty_replacements ADD COLUMN owner_user_id INT(11) NULL AFTER dataset_name";
    if ($conn->query($alter_sql)) {
        echo "✅ Added owner_user_id column to warranty_replacements\n";
    } else {
        echo "❌ Error adding column: " . $conn->error . "\n";
    }
} else {
    echo "✅ owner_user_id column already exists\n";
}

// Also add index for performance
$check_idx = $conn->query("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME='warranty_replacements' AND INDEX_NAME='idx_warranty_owner'");
if ($check_idx && $check_idx->num_rows === 0) {
    $conn->query("CREATE INDEX idx_warranty_owner ON warranty_replacements(owner_user_id)");
    echo "✅ Added index on owner_user_id\n";
}
?>