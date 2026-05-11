<?php
require 'db_config.php';

if (!$conn) {
    die("Database connection failed\n");
}

$r = $conn->query('SELECT DISTINCT owner_user_id FROM delivery_records');
if ($r) {
    echo "Distinct owner_user_id values:\n";
    while($row = $r->fetch_assoc()) {
      echo "  - " . ($row['owner_user_id'] ?? 'NULL') . "\n";
    }
} else {
    echo "Query error: " . $conn->error . "\n";
}

// Count totals
$dr = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records');
if ($dr) {
    $row = $dr->fetch_assoc();
    echo "\nTotal delivery_records: " . $row['cnt'] . "\n";
} else {
    echo "Count query error\n";
}
?>