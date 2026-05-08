<?php
require_once 'db_config.php';

echo "=== REMOVING DUPLICATES ===\n\n";

// Keep only the first occurrence (lowest id) of each invoice_no + item_code combination
// Delete all others

$delete_sql = "
DELETE FROM warranty_replacements
WHERE id NOT IN (
    SELECT MIN(id)
    FROM (
        SELECT MIN(id) as min_id
        FROM warranty_replacements
        GROUP BY invoice_no, item_code
    ) as temp
)
";

// First count how many will be deleted
$count_sql = "
SELECT COUNT(*) as to_delete FROM warranty_replacements
WHERE id NOT IN (
    SELECT MIN(id)
    FROM (
        SELECT MIN(id) as min_id
        FROM warranty_replacements
        GROUP BY invoice_no, item_code
    ) as temp
)
";

$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$to_delete = intval($count_row['to_delete']);

echo "Records to delete: " . $to_delete . "\n";
echo "Records to keep: " . (432 - $to_delete) . "\n\n";

if ($to_delete > 0) {
    echo "Proceeding with deletion...\n";
    if ($conn->query($delete_sql)) {
        echo "✅ Deletion successful!\n";
        
        // Verify
        $verify_sql = "SELECT COUNT(*) as count FROM warranty_replacements";
        $verify_result = $conn->query($verify_sql);
        $verify_row = $verify_result->fetch_assoc();
        $final_count = intval($verify_row['count']);
        
        echo "Final count: " . $final_count . " warranty items remaining\n";
        echo "Deleted: " . $to_delete . " duplicates\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
} else {
    echo "No duplicates found!\n";
}

$conn->close();
?>
