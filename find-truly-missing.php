<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Get reference list (excluding headers and internal/warehouse items)
$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');
$max_row = $sheet->getHighestRow();

$reference_list = [];
for ($row = 2; $row <= $max_row; $row++) {
    $company = trim($sheet->getCell('A' . $row)->getValue());
    
    // Skip non-clients
    if (empty($company) || 
        $company === '(Blanks)' || 
        $company === 'Sold To' ||
        strpos($company, 'replaced to') !== false ||
        in_array($company, ['Andison Manila Use', 'Warranty Replacement', 'Sales record not found', 'to Andison Manila'])) {
        continue;
    }
    
    $reference_list[] = $company;
}

sort($reference_list);

// Get database companies
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'
    ORDER BY company_name");

$db_companies = [];
while ($row = $result->fetch_assoc()) {
    $db_companies[] = $row['company_name'];
}

echo "Reference list companies: " . count($reference_list) . "\n";
echo "Database companies: " . count($db_companies) . "\n";
echo "Missing: " . (count($reference_list) - count($db_companies)) . "\n\n";

// Find missing
$missing = [];
foreach ($reference_list as $comp) {
    if (!in_array($comp, $db_companies)) {
        $missing[] = $comp;
    }
}

if (!empty($missing)) {
    echo "Missing companies (" . count($missing) . "):\n";
    foreach ($missing as $i => $comp) {
        echo ($i + 1) . ". " . $comp . "\n";
    }
}
?>
