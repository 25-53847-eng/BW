<?php
require '../db_config.php';

echo "=== DATABASE SETTINGS ===\n";

// Check database collation
$db_collation = $conn->query("SELECT DEFAULT_COLLATION_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = DATABASE()")->fetch_assoc();
echo "Database collation: " . $db_collation['DEFAULT_COLLATION_NAME'] . "\n\n";

// Check current session collation settings
$session = $conn->query("SHOW SESSION VARIABLES LIKE '%collation%'")->fetch_all(MYSQLI_ASSOC);
echo "=== SESSION COLLATION VARIABLES ===\n";
foreach($session as $var) {
    echo $var['Variable_name'] . ": " . $var['Value'] . "\n";
}

// Try a test INSERT to see actual error
echo "\n=== TESTING TRIGGER WITH TEST INSERT ===\n";

$test_sql = "INSERT INTO delivery_records (
    delivery_month, delivery_day, delivery_year, item_code, item_name, 
    company_name, quantity, uom, status
) VALUES (
    'May', 5, 2026, 'TEST-123', 'Test Item',
    'Test Company', 1, 'pcs', 'Delivered'
)";

if($conn->query($test_sql)) {
    echo "✓ Test INSERT successful\n";
    // Clean up
    $conn->query("DELETE FROM delivery_records WHERE item_code = 'TEST-123' LIMIT 1");
} else {
    echo "✗ INSERT failed: " . $conn->error . "\n";
}

$conn->close();
?>

