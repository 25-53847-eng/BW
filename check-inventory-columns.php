<?php
require 'vendor/autoload.php';
require 'db_config.php';

// Check inventory table structure
$query = "DESCRIBE inventory";
$result = $conn->query($query);

echo "Inventory Table Structure:\n";
echo "==========================\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}

$conn->close();
?>
