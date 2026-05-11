<?php
require 'db_config.php';

// Check the currently excluded entries
$excluded = ['Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila'];

echo "Currently excluded from client companies page:\n";
foreach ($excluded as $name) {
    $sql = "SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['cnt'] > 0) {
        echo "- '$name': " . $row['cnt'] . " records\n";
    }
}

// Get total unique
$sql = "SELECT COUNT(DISTINCT company_name) as cnt FROM delivery_records";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "\nTotal unique company names in database: " . $row['cnt'] . "\n";

// Calculate what we have vs need
echo "\n=== CALCULATION ===\n";
echo "Database total unique: " . $row['cnt'] . "\n";
echo "Minus excluded (Stock Addition, Andison Manila, etc): need to verify\n";
echo "Page currently shows: 187\n";
echo "User wants: 196\n";
echo "Difference needed: 9\n";

echo "\n=== SOLUTION ===\n";
echo "Missing company 'Andison Manila Use': +1\n";
echo "Should 'to Andison Manila' be included? (48 records)\n";
echo "Should other excluded items be included?\n";

// Check if maybe the bad entries are in the export list count
$bad_entries = ['(Blanks)', 'Sold To', 'Warranty Replacement', 'Sales record not found', 
                'replaced to MCXL-XWHM  SN : KA425-0013916', 
                'replaced to Ultra Unit SN : 5220ULT01242400142', 
                'replaced to XT-XWHM  SN MA225-006997'];

echo "\n=== BAD ENTRIES IN DATABASE ===\n";
foreach ($bad_entries as $name) {
    $sql = "SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if ($row['cnt'] > 0) {
        echo "- '$name': " . $row['cnt'] . " records\n";
    }
}
?>
