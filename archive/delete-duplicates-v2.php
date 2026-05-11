<?php
require_once 'db_config.php';

echo "=== REMOVING COMPLETE ROW DUPLICATES (Method 2) ===\n\n";

// Count before
$before_sql = "SELECT COUNT(*) as count FROM warranty_replacements";
$before_result = $conn->query($before_sql);
$before_row = $before_result->fetch_assoc();
$before_count = intval($before_row['count']);

echo "Records BEFORE: " . $before_count . "\n\n";

// Create a temporary table with min ids to keep
$create_temp_sql = "
CREATE TEMPORARY TABLE temp_keep_ids AS
SELECT MIN(id) as id
FROM warranty_replacements
GROUP BY invoice_no, item_code, serial_no, quantity, company_name, sold_to
";

if (!$conn->query($create_temp_sql)) {
    echo "❌ Error creating temp table: " . $conn->error . "\n";
    $conn->close();
    exit;
}

echo "✓ Temp table created\n";

// Delete all ids NOT in the temp table
$delete_sql = "DELETE FROM warranty_replacements WHERE id NOT IN (SELECT id FROM temp_keep_ids)";

if ($conn->query($delete_sql)) {
    echo "✓ Deletion executed\n\n";
    
    // Count after
    $after_result = $conn->query($before_sql);
    $after_row = $after_result->fetch_assoc();
    $after_count = intval($after_row['count']);
    
    $deleted = $before_count - $after_count;
    
    echo "Records AFTER: " . $after_count . "\n";
    echo "Duplicates removed: " . $deleted . "\n";
    
    if ($deleted > 0) {
        echo "\n✅ Cleanup successful!\n";
    } else {
        echo "\n⚠️  No duplicates were removed\n";
    }
} else {
    echo "❌ Error deleting: " . $conn->error . "\n";
}

// Clean up temp table
$conn->query("DROP TEMPORARY TABLE IF EXISTS temp_keep_ids");

$conn->close();
?>
