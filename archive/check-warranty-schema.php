<?php
require 'db_config.php';

echo "=== Warranty Replacements Columns ===\n";
$desc = $conn->query('DESCRIBE warranty_replacements');
while($row = $desc->fetch_assoc()) {
  echo $row['Field'] . "\n";
}
?>