<?php
require_once 'db_config.php';

// Check warranty replacement rows
echo "=== WARRANTY REPLACEMENT ROWS ===\n\n";

// Count warranty items with warranty_flag = 1
$sql1 = "SELECT COUNT(*) as count FROM warranty_replacements WHERE warranty_flag = 1";
$result1 = $conn->query($sql1);
$row1 = $result1->fetch_assoc();
echo "Warranty flagged items (warranty_flag=1): " . number_format($row1['count']) . "\n";

// Check total with red_text_detected = 1
$sql3 = "SELECT COUNT(*) as count FROM warranty_replacements WHERE red_text_detected = 1";
$result3 = $conn->query($sql3);
$row3 = $result3->fetch_assoc();
echo "Items with red text detected: " . number_format($row3['count']) . "\n";

// Just show the total again
$sql4 = "SELECT COUNT(*) as count FROM warranty_replacements";
$result4 = $conn->query($sql4);
$row4 = $result4->fetch_assoc();
echo "Total warranty_replacements records: " . number_format($row4['count']) . "\n";

// Check delivery_records with warranty flags
echo "\n=== IN DELIVERY_RECORDS ===\n";
$sql5 = "SELECT COUNT(*) as count FROM delivery_records WHERE LOWER(TRIM(COALESCE(groupings, ''))) LIKE '%warranty replacement%'";
$result5 = $conn->query($sql5);
$row5 = $result5->fetch_assoc();
echo "Delivery records marked WARRANTY REPLACEMENT: " . number_format($row5['count']) . "\n";

// Also check for red text in delivery_records
$sql6 = "SELECT COUNT(*) as count FROM delivery_records WHERE LOWER(TRIM(COALESCE(highlight_color, ''))) LIKE '%red%' OR LOWER(TRIM(COALESCE(highlight_color, ''))) = 'ff0000'";
$result6 = $conn->query($sql6);
$row6 = $result6->fetch_assoc();
echo "Delivery records with red highlight: " . number_format($row6['count']) . "\n";

$conn->close();
?>
