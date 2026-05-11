<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $file = 'BW GAS DATA.xlsx';
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        die();
    }
    
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    
    echo "Sheet name: " . $sheet->getTitle() . "\n";
    echo "Max Row: " . $sheet->getHighestRow() . "\n";
    echo "Max Col: " . $sheet->getHighestColumn() . "\n\n";
    
    // Get headers
    echo "Headers:\n";
    $headers = [];
    $col = 1;
    for ($col = 1; $col <= 20; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, 1);
        $value = $cell->getValue();
        if (empty($value)) break;
        $headers[$col] = $value;
        echo "Col $col: $value\n";
    }
    
    echo "\nFirst 10 data rows:\n";
    for ($row = 2; $row <= min(11, $sheet->getHighestRow()); $row++) {
        $values = [];
        for ($col = 1; $col <= count($headers); $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $row);
            $values[$col] = $cell->getValue();
        }
        echo "Row $row: ";
        foreach ($values as $col => $val) {
            if ($headers[$col]) {
                echo $headers[$col] . "=" . $val . " | ";
            }
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
