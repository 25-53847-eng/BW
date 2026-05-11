<?php
require 'vendor/autoload.php';

$file = 'BW GAS DATA.xlsx';
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

echo "Sheet: " . $sheet->getTitle() . "\n";
echo "Total Rows: " . $sheet->getHighestRow() . "\n\n";

// Get headers
$headers = [];
$maxCol = $sheet->getHighestColumn();
for ($col = 'A'; $col <= $maxCol; $col++) {
    $cell = $sheet->getCell($col . '1');
    $headers[$col] = $cell->getValue();
}

echo "Columns:\n";
foreach ($headers as $col => $header) {
    echo "$col: $header\n";
}

echo "\n\nFirst 10 data rows (sample):\n";
for ($row = 2; $row <= min(11, $sheet->getHighestRow()); $row++) {
    for ($col = 'A'; $col <= $maxCol; $col++) {
        $cell = $sheet->getCell($col . $row);
        $value = $cell->getValue();
        echo ($value ?? 'NULL') . " | ";
    }
    echo "\n";
}

// Find inventory column
echo "\n\nSearching for inventory-related columns:\n";
foreach ($headers as $col => $header) {
    if (stripos($header, 'inventory') !== false || stripos($header, 'qty') !== false || stripos($header, 'quantity') !== false || stripos($header, 'stock') !== false) {
        echo "Found: Column $col = $header\n";
    }
}
?>
