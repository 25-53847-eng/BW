<?php
require_once 'db_config.php';

echo "=== REMOVING COMPLETE ROW DUPLICATES ===\n\n";

// Keep only the first occurrence (lowest id) of each duplicate group
// Delete rows where id > min(id) for the same invoice_no, item_code, serial_no, quantity, company_name, sold_to combo

$delete_sql = "
DELETE FROM warranty_replacements
WHERE id NOT IN (
    SELECT MIN(id)
    FROM (
        SELECT MIN(id) as min_id
        FROM warranty_replacements
        GROUP BY invoice_no, item_code, serial_no, quantity, company_name, sold_to
    ) as temp
)
";

// Count before
$before_sql = "SELECT COUNT(*) as count FROM warranty_replacements";
$before_result = $conn->query($before_sql);
$before_row = $before_result->fetch_assoc();
$before_count = intval($before_row['count']);

echo "Records BEFORE: " . $before_count . "\n";

// Execute deletion
if ($conn->query($delete_sql)) {
    echo "✅ Deletion successful!\n\n";
    
    // Count after
    $after_result = $conn->query($before_sql);
    $after_row = $after_result->fetch_assoc();
    $after_count = intval($after_row['count']);
    
    $deleted = $before_count - $after_count;
    
    echo "Records AFTER: " . $after_count . "\n";
    echo "Duplicates removed: " . $deleted . "\n";
    echo "\n✅ Cleanup complete!\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

$conn->close();
?>
