<?php
require '../db_config.php';

echo "Checking highlight_color in database:\n\n";

// Check how many records have highlight_color
$r1 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE highlight_color IS NOT NULL AND highlight_color != ''");
$row1 = $r1->fetch_assoc();
echo "Records WITH highlight_color: " . $row1['cnt'] . "\n";

// Check how many records have cell_styles
$r2 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE cell_styles IS NOT NULL AND cell_styles != ''");
$row2 = $r2->fetch_assoc();
echo "Records WITH cell_styles: " . $row2['cnt'] . "\n";

echo "\n\nSample highlight_color values:\n";
$r3 = $conn->query("SELECT id, item_code, highlight_color FROM delivery_records WHERE highlight_color IS NOT NULL AND highlight_color != '' ORDER BY id DESC LIMIT 10");

while ($row = $r3->fetch_assoc()) {
    echo "- ID: " . $row['id'] . ", Code: " . $row['item_code'] . ", Color: " . $row['highlight_color'] . "\n";
}

echo "\n\nSample cell_styles values (first 3 chars):\n";
$r4 = $conn->query("SELECT id, item_code, cell_styles FROM delivery_records WHERE cell_styles IS NOT NULL AND cell_styles != '' ORDER BY id DESC LIMIT 10");

while ($row = $r4->fetch_assoc()) {
    $styles_preview = substr($row['cell_styles'], 0, 80);
    echo "- ID: " . $row['id'] . ", Code: " . $row['item_code'] . ", Styles: " . $styles_preview . "...\n";
}
?>

