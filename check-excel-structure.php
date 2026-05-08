<?php
require 'vendor/autoload.php';

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('BW Gas Detector Current Sales Record.xlsx');
$sheet = $spreadsheet->getActiveSheet();

echo "Excel Sheet Headers (first 5 rows):\n\n";
for ($i = 1; $i <= 5; $i++) {
    echo "Row $i: ";
    for ($j = 1; $j <= 10; $j++) {
        $value = $sheet->getCellByColumnAndRow($j, $i)->getValue();
        echo "Col$j=" . substr($value, 0, 20) . " | ";
    }
    echo "\n";
}

// Count total rows
$highestRow = $sheet->getHighestRow();
echo "\nTotal rows: $highestRow\n";
?>
