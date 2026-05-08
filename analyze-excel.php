<?php
require_once 'vendor/autoload.php';
require_once 'db_config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

echo "Reading Excel file...\n";
$file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($file);

// Get sheet names
$sheets = $spreadsheet->getSheetNames();
echo "Available sheets:\n";
foreach ($sheets as $idx => $name) {
    echo ($idx + 1) . ". $name\n";
}

// Read the first sheet (2024 to NOW BW Sales Record)
$sheet = $spreadsheet->getSheet(0);
echo "\nReading sheet: " . $sheet->getTitle() . "\n";

$highest_row = $sheet->getHighestRow();
$highest_col = $sheet->getHighestColumn();
echo "Dimensions: A1:$highest_col$highest_row\n\n";

// Get column headers
$headers = [];
for ($col = 1; $col <= 13; $col++) {
    $cell = $sheet->getCellByColumnAndRow($col, 1);
    $headers[$col] = $cell->getValue();
}

// Count non-empty rows
$non_empty_count = 0;
$sample_rows = [];

for ($row = 2; $row <= $highest_row; $row++) {
    $row_data = [];
    $has_data = false;
    
    for ($col = 1; $col <= 13; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $row);
        $value = $cell->getValue();
        $row_data[$headers[$col]] = $value;
        if (!empty($value)) {
            $has_data = true;
        }
    }
    
    if ($has_data) {
        $non_empty_count++;
        if (count($sample_rows) < 5) {
            $sample_rows[] = $row_data;
        }
    }
}

echo "Non-empty data rows: $non_empty_count\n\n";

echo "=== First 5 data rows ===\n";
foreach ($sample_rows as $i => $row) {
    echo "\nRow " . ($i + 1) . ":\n";
    echo "  Invoice: " . ($row['Invoice No.'] ?? 'N/A') . "\n";
    echo "  Date: " . ($row['Date'] ?? 'N/A') . "\n";
    echo "  Item: " . ($row['Item'] ?? 'N/A') . "\n";
    echo "  Description: " . ($row['Description'] ?? 'N/A') . "\n";
    echo "  Qty: " . ($row['Qty.'] ?? 'N/A') . "\n";
    echo "  Serial No: " . ($row['Serial No.'] ?? 'N/A') . "\n";
    echo "  Sold To: " . ($row['Sold To'] ?? 'N/A') . "\n";
    echo "  Date Delivered: " . ($row['Date Delivered'] ?? 'N/A') . "\n";
}
?>
