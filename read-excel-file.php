<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'BW Gas Detector Current Sales Record.xlsx';
echo "=== Reading Excel File ===\n";
echo "File: $file\n\n";

try {
    $spreadsheet = IOFactory::load($file);
    
    echo "Sheet count: " . count($spreadsheet->getAllSheets()) . "\n";
    echo "Sheet names: " . implode(", ", $spreadsheet->getSheetNames()) . "\n\n";
    
    $sheet = $spreadsheet->getActiveSheet();
    echo "=== Active Sheet Data ===\n";
    echo "Sheet name: " . $sheet->getTitle() . "\n";
    echo "Dimension: " . $sheet->calculateWorksheetDimension() . "\n\n";
    
    // Get column headers (first row)
    echo "=== Column Headers ===\n";
    $headers = [];
    for ($col = 1; $col <= 20; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, 1);
        $value = $cell->getValue();
        if (empty($value)) break;
        $headers[$col] = $value;
        echo "$col: " . $value . "\n";
    }
    
    // Show first 5 data rows
    echo "\n=== First 5 Data Rows ===\n";
    for ($row = 2; $row <= 6; $row++) {
        echo "\nRow $row:\n";
        for ($col = 1; $col <= count($headers); $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $value = $cell->getValue();
            echo "  " . $headers[$col] . ": " . $value . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
