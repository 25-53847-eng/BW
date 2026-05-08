<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

echo "=== Analyzing Excel 'Sold To' column ===\n\n";

$sold_to_values = [];
$highest_row = $sheet->getHighestRow();

// Column I = Sold To
for ($row = 2; $row <= $highest_row; $row++) {
    $cell = $sheet->getCellByColumnAndRow(9, $row);
    $value = trim((string) $cell->getValue());
    
    if (empty($value) || $value === '-') {
        continue; // Skip empty
    }
    
    $sold_to_values[$value] = ($sold_to_values[$value] ?? 0) + 1;
}

echo "Non-empty 'Sold To' values found:\n";
arsort($sold_to_values);
$total = 0;
$client_count = 0;
foreach ($sold_to_values as $value => $count) {
    $total += $count;
    $is_internal = (strpos(strtolower($value), 'andison') !== false || strpos(strtolower($value), 'stock') !== false || strpos(strtolower($value), 'inventory') !== false);
    $marker = $is_internal ? '❌ INTERNAL' : '✅ CLIENT';
    echo "$marker: '$value' ($count records)\n";
    if (!$is_internal) {
        $client_count += $count;
    }
}

echo "\n=== Summary ===\n";
echo "Total rows with valid Sold To: $total\n";
echo "Client company sales: $client_count\n";
echo "Internal/routing records: " . ($total - $client_count) . "\n";

// Show how many rows are completely empty (all fields)
echo "\n=== Checking for completely empty rows ===\n";
$empty_count = 0;
for ($row = 2; $row <= $highest_row; $row++) {
    $is_empty = true;
    for ($col = 1; $col <= 13; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        if (!empty((string) $cell->getValue())) {
            $is_empty = false;
            break;
        }
    }
    if ($is_empty) {
        $empty_count++;
    }
}
echo "Completely empty rows: $empty_count\n";
echo "Rows with some data: " . ($highest_row - 1 - $empty_count) . "\n";
?>
