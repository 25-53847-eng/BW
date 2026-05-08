<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

echo "=== Excel Sheet Column Mapping (Row 1 - Headers) ===\n\n";

// Get all column headers
for ($col = 1; $col <= 21; $col++) {
    $cell = $sheet->getCellByColumnAndRow($col, 1);
    $header = $cell->getValue();
    if (empty($header)) break;
    echo "Col " . chr(64 + $col) . ": " . $header . "\n";
}

echo "\n\n=== Sample Data (Row 5) ===\n";
for ($col = 1; $col <= 13; $col++) {
    $cell = $sheet->getCellByColumnAndRow($col, 5);
    $value = $cell->getValue();
    $header = $sheet->getCellByColumnAndRow($col, 1)->getValue();
    echo "$header: " . var_export($value, true) . "\n";
}

// Check what column has "Andison Industrial"
echo "\n\n=== Looking for 'Andison Industrial' in the data (Row 5) ===\n";
for ($col = 1; $col <= 20; $col++) {
    $cell = $sheet->getCellByColumnAndRow($col, 5);
    $value = (string) $cell->getValue();
    if (strpos(strtolower($value), 'andison') !== false) {
        $header = $sheet->getCellByColumnAndRow($col, 1)->getValue();
        echo "Found in Column " . chr(64 + $col) . " ($header): $value\n";
    }
}
?>
