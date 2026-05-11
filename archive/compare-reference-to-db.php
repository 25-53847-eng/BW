<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');
$max_row = $sheet->getHighestRow();

// Get reference list companies
$reference_companies = [];
for ($row = 2; $row <= $max_row; $row++) {
    $company = trim($sheet->getCell('A' . $row)->getValue());
    if (!empty($company) && !in_array($company, ['(Blanks)', 'Sold To', 'Andison Manila Use', 'Sales record not found', 'Warranty Replacement'])) {
        // Skip warranty replacements
        if (strpos($company, 'replaced to') === false) {
            $reference_companies[] = $company;
        }
    }
}

// Get database companies
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'
    ORDER BY company_name");

$db_companies = [];
while ($row = $result->fetch_assoc()) {
    $db_companies[] = $row['company_name'];
}

echo "Reference list companies: " . count($reference_companies) . "\n";
echo "Database companies: " . count($db_companies) . "\n\n";

// Find missing
$missing_in_db = [];
foreach ($reference_companies as $comp) {
    if (!in_array($comp, $db_companies)) {
        $missing_in_db[] = $comp;
    }
}

// Find extra (in DB but not in reference)
$extra_in_db = [];
foreach ($db_companies as $comp) {
    if (!in_array($comp, $reference_companies)) {
        $extra_in_db[] = $comp;
    }
}

if (!empty($missing_in_db)) {
    echo "Missing from DB (" . count($missing_in_db) . "):\n";
    foreach ($missing_in_db as $comp) {
        echo "  - " . $comp . "\n";
    }
    echo "\n";
}

if (!empty($extra_in_db)) {
    echo "Extra in DB (" . count($extra_in_db) . "):\n";
    foreach ($extra_in_db as $comp) {
        echo "  - " . $comp . "\n";
    }
}

if (empty($missing_in_db) && empty($extra_in_db)) {
    echo "✅ Perfect match! All companies synced.\n";
    echo "\nTotal client companies: " . count($db_companies) . "\n";
}
?>
