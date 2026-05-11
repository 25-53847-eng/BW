<?php
require 'vendor/autoload.php';

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('BW Gas Detector Current Sales Record.xlsx');
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');

echo "Companies from 'Export List_Sold To' sheet:\n\n";

$excel_companies = [];
$highestRow = $sheet->getHighestRow();

for ($i = 2; $i <= $highestRow; $i++) { 
    $company = trim($sheet->getCellByColumnAndRow(1, $i)->getValue());
    $count = $sheet->getCellByColumnAndRow(2, $i)->getValue();
    
    if ($company && $company !== '') {
        $excel_companies[$company] = $count;
        echo "$company (Count: $count)\n";
    }
}

echo "\n\nTotal companies in Export sheet: " . count($excel_companies) . "\n";
?>
