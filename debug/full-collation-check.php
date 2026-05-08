<?php
require '../db_config.php';

echo "=== ALL TABLES & THEIR COLLATIONS ===\n";
$tables = $conn->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");

$unicode_count = 0;
$general_count = 0;
$mixed = [];

while($row = $tables->fetch_assoc()) {
    $name = $row['TABLE_NAME'];
    $collation = $row['TABLE_COLLATION'];
    
    if(strpos($collation, 'general') !== false) {
        echo "  ? $name: $collation\n";
        $general_count++;
        $mixed[] = $name;
    } elseif(strpos($collation, 'unicode') !== false) {
        echo "  ? $name: $collation\n";
        $unicode_count++;
    } elseif($collation == NULL || $collation == '') {
        echo "  - $name: (VIEW or NULL)\n";
    } else {
        echo "  ? $name: $collation\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Unicode CI: $unicode_count\n";
echo "General CI: $general_count\n";

if(count($mixed) > 0) {
    echo "\n?? Tables still using utf8mb4_general_ci:\n";
    foreach($mixed as $tbl) {
        echo "  - $tbl\n";
    }
}

$conn->close();
?>

