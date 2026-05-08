<?php
require_once '../db_config.php';

echo "=== FOREIGN KEY CONSTRAINTS ===\n";
$fk = $conn->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'delivery_records' AND REFERENCED_TABLE_NAME IS NOT NULL");

if($fk->num_rows > 0) {
    while($row = $fk->fetch_assoc()) {
        echo "{$row['CONSTRAINT_NAME']}: {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
} else {
    echo "None\n";
}

echo "\n=== TRIGGERS ===\n";
$triggers = $conn->query("SHOW TRIGGERS WHERE `Table` = 'delivery_records'");
if($triggers->num_rows > 0) {
    while($row = $triggers->fetch_assoc()) {
        echo "Trigger: {$row['Trigger']}\n";
        echo "  Event: {$row['Event']}\n";
        echo "  Timing: {$row['Timing']}\n";
    }
} else {
    echo "None\n";
}

echo "\n=== TABLE ENGINE & COLLATION ===\n";
$engine = $conn->query("SELECT ENGINE, TABLE_COLLATION, ROW_FORMAT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'delivery_records' AND TABLE_SCHEMA = DATABASE()");
$row = $engine->fetch_assoc();
echo "Engine: {$row['ENGINE']}\n";
echo "Collation: {$row['TABLE_COLLATION']}\n";
echo "Row Format: {$row['ROW_FORMAT']}\n";

$conn->close();
?>

