<?php
require 'vendor/autoload.php';

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load('BW Gas Detector Current Sales Record.xlsx');

echo "Sheet names in Excel:\n";
foreach ($spreadsheet->getSheetNames() as $name) {
    echo "- $name\n";
}

// Check each sheet for company counts
foreach ($spreadsheet->getSheetNames() as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $highestRow = $sheet->getHighestRow();
    echo "\n$sheetName: $highestRow rows\n";
    
    // Check first row for headers
    echo "  Headers: ";
    for ($j = 1; $j <= 10; $j++) {
        $val = $sheet->getCellByColumnAndRow($j, 1)->getValue();
        if ($val) echo "$val | ";
    }
    echo "\n";
}
?>
