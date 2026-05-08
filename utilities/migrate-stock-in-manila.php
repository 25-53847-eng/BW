<?php
require_once __DIR__ . '/../db_config.php';

// First migration - catch all without dataset_name check
$sql1 = "UPDATE delivery_records 
        SET company_name = 'Andison Manila', 
            sold_to = 'Andison Manila',
            updated_at = CURRENT_TIMESTAMP
        WHERE LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
        AND company_name != 'Andison Manila'";

if ($conn->query($sql1)) {
    echo "Batch 1 - Migrated " . $conn->affected_rows . " records.\n";
} else {
    echo "Error in batch 1: " . $conn->error . "\n";
}

// Check what's still there
$check = $conn->query("SELECT COUNT(*), GROUP_CONCAT(DISTINCT dataset_name) FROM delivery_records 
                        WHERE LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'");
if ($check) {
    $row = $check->fetch_row();
    echo "Remaining 'Stock in Manila' records: " . $row[0] . "\n";
    echo "Dataset names: " . ($row[1] ?? 'NULL') . "\n";
}

$conn->close();
?>



