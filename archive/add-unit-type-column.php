<?php
/**
 * Migration script to add unit_type column to delivery_records table
 * Run once to add the column - safe to run multiple times (checks if exists)
 */

require_once __DIR__ . '/db_config.php';

echo "Checking for unit_type column...\n";

// Check if column exists
$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'unit_type' 
        AND TABLE_SCHEMA = DATABASE()";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "✓ unit_type column already exists\n";
} else {
    echo "Adding unit_type column...\n";
    $sql = "ALTER TABLE delivery_records ADD COLUMN unit_type VARCHAR(10) NULL COMMENT \"Unit Type (1a, 1b, 2a, 2b, 3a, 4a)\"";
    
    if ($conn->query($sql)) {
        echo "✓ unit_type column added successfully\n";
        
        // Add index for performance
        $sql = "ALTER TABLE delivery_records ADD INDEX idx_unit_type (unit_type)";
        if ($conn->query($sql)) {
            echo "✓ Index created on unit_type column\n";
        }
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

echo "Migration complete!\n";
$conn->close();
?>
