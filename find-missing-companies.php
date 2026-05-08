<?php
require 'vendor/autoload.php';
require 'db_config.php';

// Read Excel file
$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('BW Gas Detector Current Sales Record.xlsx');
$sheet = $spreadsheet->getActiveSheet();

// Extract unique companies from Excel (Column 9 = Sold To)
$excel_companies = [];
$highestRow = $sheet->getHighestRow();

for ($i = 5; $i <= $highestRow; $i++) { // Start from row 5 (skip headers)
    $company = trim($sheet->getCellByColumnAndRow(9, $i)->getValue());
    if ($company && $company !== '' && $company !== 'Sold To') {
        $excel_companies[$company] = true;
    }
}

echo "Excel unique companies: " . count($excel_companies) . "\n";

// Get companies from database
$db_companies = [];
$sql = "SELECT DISTINCT 
        NULLIF(TRIM(CASE
            WHEN company_name IS NOT NULL AND company_name != '' AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila') 
            THEN company_name
            ELSE ''
        END), '') as company_name
        FROM delivery_records 
        WHERE company_name IS NOT NULL
        AND NULLIF(TRIM(CASE
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

echo "Database unique companies: " . count($db_companies) . "\n\n";

// Find missing companies
$missing = array_diff_key($excel_companies, $db_companies);
echo "Missing from database (" . count($missing) . "):\n";
foreach ($missing as $company => $true) {
    echo "- " . $company . "\n";
}

echo "\n\nExtra in database (not in Excel):\n";
$extra = array_diff_key($db_companies, $excel_companies);
foreach ($extra as $company => $true) {
    echo "- " . $company . "\n";
}
?>
