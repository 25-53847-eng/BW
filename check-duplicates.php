<?php
require_once 'db_config.php';

echo "=== CHECKING FOR DUPLICATES ===\n\n";

// First, let's see what columns would identify a unique warranty item
// Typically: invoice_no + item_code + serial_no
$sql = "
SELECT 
    invoice_no, item_code, serial_no, 
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id) as ids
FROM warranty_replacements
GROUP BY invoice_no, item_code, serial_no
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC
";

$result = $conn->query($sql);
$duplicate_count = 0;
$duplicate_groups = 0;

echo "Duplicates found (invoice_no + item_code + serial_no):\n";
while ($row = $result->fetch_assoc()) {
    $count = intval($row['duplicate_count']);
    $duplicate_groups++;
    $duplicate_count += $count - 1; // Each group has count-1 duplicates
    echo "  " . ($row['invoice_no'] ?? 'NULL') . " | " . ($row['item_code'] ?? 'NULL') . " | " . ($row['serial_no'] ?? 'NULL') . " => " . $count . " copies (IDs: " . $row['ids'] . ")\n";
}

echo "\n=== SUMMARY ===\n";
echo "Duplicate groups found: " . $duplicate_groups . "\n";
echo "Total rows to remove: " . $duplicate_count . "\n";
echo "Current total: 432\n";
echo "After removal: " . (432 - $duplicate_count) . "\n";

$conn->close();
?>
