<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($file);
$sheet = $spreadsheet->getSheet(0);

echo "=== Unique Company Names in Excel Sheet ===\n\n";

$companies = [];
$highest_row = $sheet->getHighestRow();

// Get company name from column J (10)
for ($row = 2; $row <= $highest_row; $row++) {
    $cell = $sheet->getCellByColumnAndRow(10, $row);  // Column J = Sold To
    $value = trim((string) $cell->getValue());
    if (!empty($value) && $value !== '-') {
        $companies[$value] = ($companies[$value] ?? 0) + 1;
    }
}

echo "Unique 'Sold To' values found:\n";
arsort($companies);
foreach ($companies as $company => $count) {
    echo "- '$company': $count records\n";
}

echo "\n\nNow checking the 'Export List_Sold To' sheet to see available clients...\n";

// Check if there's a sheet with client list
try {
    $clientSheet = $spreadsheet->getSheetByName('Export List_Sold To');
    if ($clientSheet) {
        echo "\n=== Available Clients in 'Export List_Sold To' Sheet ===\n";
        $highest_row = $clientSheet->getHighestRow();
        $clients = [];
        for ($row = 2; $row <= $highest_row && $row <= 50; $row++) {
            $cell = $clientSheet->getCellByColumnAndRow(1, $row);
            $value = trim((string) $cell->getValue());
            if (!empty($value)) {
                $clients[] = $value;
            }
        }
        foreach ($clients as $idx => $client) {
            echo ($idx + 1) . ". $client\n";
        }
    }
} catch (Exception $e) {
    echo "Could not read Export List_Sold To sheet: " . $e->getMessage() . "\n";
}
?>
