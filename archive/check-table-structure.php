<?php
require 'db_config.php';

// Check table structure
$result = $conn->query('DESCRIBE delivery_records');
echo "delivery_records columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// Check what company values exist
echo "\n\nChecking for 'Sales record not found':\n";
$sql = "SELECT DISTINCT company_name FROM delivery_records WHERE company_name LIKE '%Sales record%' OR company_name LIKE '%not found%'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "- Found: " . $row['company_name'] . "\n";
    }
} else {
    echo "No matches found for 'Sales record not found'\n";
}

// Check unique company names containing "sales" or "record"
echo "\n\nAll unique company names:\n";
$sql = "SELECT DISTINCT company_name FROM delivery_records ORDER BY company_name LIMIT 50";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    if ($row['company_name']) {
        echo "- " . $row['company_name'] . "\n";
    }
}
?>
