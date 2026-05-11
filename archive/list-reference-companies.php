<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');
$max_row = $sheet->getHighestRow();

echo "Companies from 'Export List_Sold To' sheet:\n";
echo "Total rows: " . $max_row . "\n\n";

$companies = [];
for ($row = 2; $row <= $max_row; $row++) {
    $company = trim($sheet->getCell('A' . $row)->getValue());
    if (!empty($company)) {
        $companies[] = $company;
    }
}

echo "Unique companies in list: " . count($companies) . "\n\n";

sort($companies);
foreach ($companies as $i => $company) {
    echo ($i + 1) . ". " . $company . "\n";
}
?>
