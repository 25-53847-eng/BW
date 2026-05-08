<?php
require_once __DIR__ . '/../db_config.php';

// Update all variations of "Stock in Manila" with extra spaces
$sql = "UPDATE delivery_records 
        SET company_name = 'Andison Manila', 
            sold_to = 'Andison Manila',
            updated_at = CURRENT_TIMESTAMP
        WHERE (
            LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in%manila%'
            OR LOWER(TRIM(COALESCE(sold_to, ''))) = 'stock in manila'
            OR LOWER(TRIM(COALESCE(sold_to, ''))) = 'stock in  manila'
        )
        AND company_name NOT IN ('Andison Manila')";

if ($conn->query($sql)) {
    echo "Success! Migrated " . $conn->affected_rows . " additional records to Andison Manila.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Verify
$check = $conn->query("SELECT COUNT(*) as count FROM delivery_records WHERE sold_to LIKE '%stock in%manila%'");
if ($check) {
    $row = $check->fetch_assoc();
    echo "Remaining 'Stock in Manila' records: " . $row['count'] . "\n";
}

$conn->close();
?>


