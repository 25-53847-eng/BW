<?php
require_once 'db_config.php';

// Get companies from database
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    ORDER BY company_name");
$db_companies = [];
while ($row = $result->fetch_assoc()) {
    $db_companies[] = $row['company_name'];
}

echo "Database companies: " . count($db_companies) . "\n";
echo "Expected: 196\n";
echo "Missing: " . (196 - count($db_companies)) . "\n\n";

// Get the "Sold To" list from Excel
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
if (file_exists($excel_file)) {
    $spreadsheet = IOFactory::load($excel_file);
    $sheet = $spreadsheet->getSheetByName('2024 to NOW BW Sales Record');
    
    $sold_to_values = [];
    $start_row = 2;
    $max_row = $sheet->getHighestRow();
    
    for ($row = $start_row; $row <= $max_row; $row++) {
        $sold_to = trim($sheet->getCell('I' . $row)->getValue());
        if (!empty($sold_to) && $sold_to != '0' && $sold_to != '5') {
            if (!in_array($sold_to, $sold_to_values)) {
                $sold_to_values[] = $sold_to;
            }
        }
    }
    
    sort($sold_to_values);
    echo "Unique 'Sold To' values in Excel: " . count($sold_to_values) . "\n\n";
    
    // Find missing companies (in Excel but not in DB)
    $missing = [];
    foreach ($sold_to_values as $excel_company) {
        if (!in_array($excel_company, $db_companies)) {
            $missing[] = $excel_company;
        }
    }
    
    if (!empty($missing)) {
        echo "MISSING companies (in Excel but not in DB): " . count($missing) . "\n";
        foreach ($missing as $comp) {
            echo "  - " . $comp . "\n";
        }
    } else {
        echo "All Excel companies are in the database!\n";
    }
}
?>
