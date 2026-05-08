<?php
require_once 'db_config.php';

// These 9 should also appear (but won't show on the filtered list since they're filtered out)
// But let's add them anyway so they're in the system
$additional_companies = [
    'Andison Manila Use',
    'Warranty Replacement',
    'Sales record not found',
    'Glenda De Guzman',
    'Ernesto',
    'Renato Sagarino',
    'Zamora Use',
    'Zamora display',
    'CV Janitorial Maintenance and Utility'  // This IS a client, let me verify
];

echo "Checking for any companies that might be missing...\n\n";

foreach ($additional_companies as $company) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM delivery_records WHERE company_name = ?");
    $stmt->bind_param('s', $company);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    echo "$company: " . $row['count'] . " records\n";
}
?>
