<?php
/**
 * Fix UNIQUE Constraint Collation Issue
 */

require_once '../db_config.php';

echo "Checking UNIQUE constraints and indexes...\n\n";

try {
    // Get all indexes on delivery_records
    $indexes = $conn->query("SHOW INDEX FROM delivery_records");
    
    echo "Current indexes:\n";
    $has_unique = false;
    while($idx = $indexes->fetch_assoc()) {
        echo "  - " . $idx['Key_name'] . " (" . $idx['Column_name'] . ")\n";
        if($idx['Key_name'] === 'unique_delivery') {
            $has_unique = true;
        }
    }
    
    if($has_unique) {
        echo "\n⚠ Found UNIQUE constraint. Dropping it...\n";
        $conn->query("ALTER TABLE delivery_records DROP INDEX unique_delivery");
        echo "✓ UNIQUE constraint dropped\n";
        
        // Recreate with explicit COLLATE
        echo "\nRecreating UNIQUE constraint with utf8mb4_unicode_ci...\n";
        $result = $conn->query("ALTER TABLE delivery_records ADD UNIQUE KEY `unique_delivery` (
            delivery_month COLLATE utf8mb4_unicode_ci,
            delivery_day,
            delivery_year,
            item_code COLLATE utf8mb4_unicode_ci,
            company_name COLLATE utf8mb4_unicode_ci
        )");
        
        if($result) {
            echo "✓ UNIQUE constraint recreated\n";
        } else {
            echo "⚠ Note: UNIQUE might already exist - " . $conn->error . "\n";
        }
    }
    
    echo "\n✅ Constraint fix complete!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>


