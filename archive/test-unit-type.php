<?php
require_once 'db_config.php';

$result = $conn->query("SELECT DISTINCT unit_type FROM delivery_records WHERE unit_type IS NOT NULL AND unit_type != '' ORDER BY unit_type ASC");

if ($result) {
    echo "Unit Types Found:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . htmlspecialchars($row['unit_type']) . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>
