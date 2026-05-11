<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('2024 to NOW BW Sales Record');
$max_row = $sheet->getHighestRow();

// Get all unique sold_to values from Excel
$excel_companies = [];
for ($row = 2; $row <= $max_row; $row++) {
    $sold_to = trim($sheet->getCell('I' . $row)->getValue());
    if (!empty($sold_to) && $sold_to !== '0' && $sold_to !== '5') {
        if (!in_array($sold_to, $excel_companies)) {
            $excel_companies[] = $sold_to;
        }
    }
}

sort($excel_companies);

echo "All unique 'Sold To' values in Excel: " . count($excel_companies) . "\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($excel_companies as $i => $company) {
    echo ($i + 1) . ". " . $company . "\n";
}
?>
