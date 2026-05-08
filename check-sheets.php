<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);

echo "Excel file: " . $excel_file . "\n";
echo "Number of sheets: " . count($spreadsheet->getSheetNames()) . "\n\n";

foreach ($spreadsheet->getSheetNames() as $sheetName) {
    $sheet = $spreadsheet->getSheetByName($sheetName);
    $max_row = $sheet->getHighestRow();
    
    // Count non-empty rows
    $non_empty_rows = 0;
    for ($row = 2; $row <= $max_row; $row++) {
        $has_data = false;
        for ($col = 'A'; $col <= 'M'; $col++) {
            $cell_value = $sheet->getCell($col . $row)->getValue();
            if (!empty($cell_value) && $cell_value !== '-') {
                $has_data = true;
                break;
            }
        }
        if ($has_data) $non_empty_rows++;
    }
    
    echo "Sheet: $sheetName\n";
    echo "  Max row: $max_row\n";
    echo "  Non-empty rows: $non_empty_rows\n";
    echo "  Unique 'Sold To' values: ";
    
    $sold_to_values = [];
    for ($row = 2; $row <= $max_row; $row++) {
        $sold_to = trim($sheet->getCell('I' . $row)->getValue());
        if (!empty($sold_to) && $sold_to !== '0' && $sold_to !== '5') {
            $sold_to_values[md5($sold_to)] = $sold_to;
        }
    }
    echo count($sold_to_values) . "\n\n";
}
?>
