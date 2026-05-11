<?php
require 'vendor/autoload.php';
require 'db_config.php';

// Read Excel Export List
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('BW Gas Detector Current Sales Record.xlsx');
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');

$excel_companies = [];
$highestRow = $sheet->getHighestRow();

for ($i = 2; $i <= $highestRow; $i++) { 
    $company = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
    
    if ($company && $company !== '' && $company !== 'Sold To') {
        $excel_companies[$company] = true;
    }
}

echo "Total unique companies in Export sheet: " . count($excel_companies) . "\n";

// Get companies from database (using the same logic as client-companies.php)
$db_companies = [];
$sql = "SELECT DISTINCT 
        NULLIF(TRIM(CASE
            WHEN company_name IS NOT NULL AND company_name != '' AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila') 
            THEN company_name
            ELSE ''
        END), '') as company_name
        FROM delivery_records 
        WHERE NULLIF(TRIM(CASE
            WHEN company_name IS NOT NULL AND company_name != '' AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila') 
            THEN company_name
            ELSE ''
        END), '') IS NOT NULL
        ORDER BY company_name";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    if ($row['company_name']) {
        $db_companies[$row['company_name']] = true;
    }
}

echo "Total unique companies in Database: " . count($db_companies) . "\n\n";

// Find missing companies
$missing = array_diff_key($excel_companies, $db_companies);
echo "Missing from database (" . count($missing) . "):\n";
foreach ($missing as $company => $true) {
    echo "- " . $company . "\n";
}

echo "\n\nExtra in database (not in Excel Export list):\n";
$extra = array_diff_key($db_companies, $excel_companies);
foreach ($extra as $company => $true) {
    echo "- " . $company . "\n";
}
?>
