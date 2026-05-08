<?php
require_once 'db_config.php';

echo "=== CLEARING ALL WARRANTY ITEMS ===\n\n";

// Count before
$before_sql = "SELECT COUNT(*) as count FROM warranty_replacements";
$before_result = $conn->query($before_sql);
$before_row = $before_result->fetch_assoc();
$before_count = intval($before_row['count']);

echo "Current warranty records: " . $before_count . "\n\n";

// Delete ALL warranty records
$delete_sql = "TRUNCATE TABLE warranty_replacements";

if ($conn->query($delete_sql)) {
    echo "✅ All warranty items deleted!\n\n";
    
    // Verify
    $after_result = $conn->query($before_sql);
    $after_row = $after_result->fetch_assoc();
    $after_count = intval($after_row['count']);
    
    echo "Records remaining: " . $after_count . "\n";
    echo "\nReady for fresh import from your Excel file (233 rows) 📊\n";
} else {
    echo "❌ Error: " . $conn->error . "\n";
}

$conn->close();
?>
