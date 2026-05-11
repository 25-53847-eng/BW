<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Get one record with Andison Industrial from database
$result = $conn->query("SELECT invoice_no, item_code, serial_no, company_name FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' AND company_name = 'Andison Industrial' LIMIT 1");
$db_row = $result->fetch_assoc();

echo "=== Sample Andison Industrial record from database ===\n";
echo "Invoice: " . $db_row['invoice_no'] . "\n";
echo "Item Code: " . $db_row['item_code'] . "\n";
echo "Serial No: " . $db_row['serial_no'] . "\n";
echo "Company Name: " . $db_row['company_name'] . "\n\n";

// Now search for this record in the Excel file
echo "=== Looking for this record in Excel ===\n";

$file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);
$highest_row = $sheet->getHighestRow();

$found_row = null;
for ($row = 2; $row <= $highest_row; $row++) {
    $invoice = (string) $sheet->getCellByColumnAndRow(2, $row)->getValue();
    $item = (string) $sheet->getCellByColumnAndRow(4, $row)->getValue();
    
    if (trim($invoice) === $db_row['invoice_no'] && trim($item) === $db_row['item_code']) {
        $found_row = $row;
        break;
    }
}

if ($found_row) {
    echo "Found in Excel row: $found_row\n\n";
    echo "Row data:\n";
    for ($col = 1; $col <= 13; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, $found_row);
        $value = $cell->getValue();
        $header = $sheet->getCellByColumnAndRow($col, 1)->getValue();
        echo "  $header: " . var_export($value, true) . "\n";
    }
} else {
    echo "NOT FOUND in Excel\n";
}

// Check all unique combinations to find where Andison Industrial comes from
echo "\n\n=== Checking another way - does Andison Industrial appear anywhere in Excel? ===\n";
$found_count = 0;
for ($row = 2; $row <= min($highest_row, 100); $row++) {
    for ($col = 1; $col <= 13; $col++) {
        $value = (string) $sheet->getCellByColumnAndRow($col, $row)->getValue();
        if (strpos(strtolower($value), 'andison industrial') !== false) {
            $header = $sheet->getCellByColumnAndRow($col, 1)->getValue();
            echo "Found in Row $row, Column $header: $value\n";
            $found_count++;
            if ($found_count >= 5) break 2;
        }
    }
}
if ($found_count === 0) {
    echo "NOT FOUND in first 100 rows of Excel\n";
}
?>
