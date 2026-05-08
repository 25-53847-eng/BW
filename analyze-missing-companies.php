<?php
require 'db_config.php';

// Companies that should NOT be in the client list (bad data, placeholders, statuses)
$exclude = [
    '(Blanks)',
    'Sold To',  // Header/placeholder
    'Warranty Replacement',  // Status, not company
    'Sales record not found',  // Note, not company
    'replaced to MCXL-XWHM  SN : KA425-0013916',  // Bad data - serial number
    'replaced to Ultra Unit SN : 5220ULT01242400142',  // Bad data - serial number
    'replaced to XT-XWHM  SN MA225-006997',  // Bad data - serial number
];

// Companies to add (not currently in database)
$to_add = [
    'Andison Manila Use',
    'to Andison Manila',  // Even though filtered, we may want to allow some variants
];

// Check which are actually missing
$missing = [];
foreach ($to_add as $company) {
    $sql = "SELECT COUNT(*) as cnt FROM delivery_records 
            WHERE TRIM(company_name) = ? 
            AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $company);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['cnt'] == 0) {
        $missing[] = $company;
        echo "Missing: $company\n";
    } else {
        echo "Found: $company (Count: " . $row['cnt'] . ")\n";
    }
}

echo "\nTotal to add: " . count($missing) . "\n";

// Bad entries that exist and should be cleaned up
$bad_entries = [];
foreach ($exclude as $company) {
    $sql = "SELECT COUNT(*) as cnt FROM delivery_records 
            WHERE TRIM(company_name) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $company);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['cnt'] > 0) {
        $bad_entries[] = ['company' => $company, 'count' => $row['cnt']];
        echo "Bad entry exists: $company (Count: " . $row['cnt'] . ")\n";
    }
}

echo "\n\nSummary:\n";
echo "- Companies to add: " . count($missing) . "\n";
echo "- Bad entries to clean: " . count($bad_entries) . "\n";

// Show what we'll add
if (!empty($missing)) {
    echo "\nWill add to delivery_records:\n";
    foreach ($missing as $company) {
        echo "- $company\n";
    }
}

// Show what we'll clean
if (!empty($bad_entries)) {
    echo "\nWill fix these bad entries:\n";
    foreach ($bad_entries as $entry) {
        echo "- {$entry['company']} ({$entry['count']} records)\n";
    }
}
?>
