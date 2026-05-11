<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('2024 to NOW BW Sales Record');

$missing_companies = [
    'Argotek Innovative Exchange, Inc.',
    'CIGC Corporation',
    'CPM Construction & Gen. Services Inc.',
    'Fueltek Innovations Inc.',
    'Germand Marketing & Industrial Corporation',
    'Harmonic',
    'Henkel Philippines Inc.',
    'JAV Thermal Solutions, Inc.',
    'JD Dimalanta Construction Services',
    'JX Nippon Philippines Inc.',
    'Kapit Bisig Ugnayan Multi-Purpose Cooperative',
    'Linde Philippines Inc.',
    'Phil Gold Processing & Refining Corporation',
    'Presam Construction and General Services Inc.',
    'Samsung CNT Philippine Corporation',
    'Shimizu Philippine Contractors, Inc.',
    'Sparta Construction Corporation',
    'Victoria\'s Milling'
];

$max_row = $sheet->getHighestRow();

echo "Looking for missing companies in Excel...\n\n";

foreach ($missing_companies as $company) {
    echo "Searching for: " . $company . "\n";
    $found_count = 0;
    
    for ($row = 2; $row <= $max_row; $row++) {
        $sold_to = trim($sheet->getCell('I' . $row)->getValue());
        
        if ($sold_to === $company) {
            $found_count++;
            
            // Show first few records for this company
            if ($found_count <= 2) {
                $invoice_no = trim($sheet->getCell('B' . $row)->getValue());
                $item = trim($sheet->getCell('D' . $row)->getValue());
                $qty = trim($sheet->getCell('F' . $row)->getValue());
                
                echo "  Row $row: Invoice=" . $invoice_no . " | Item=" . $item . " | Qty=" . $qty . "\n";
            }
        }
    }
    
    echo "  Found: $found_count rows\n\n";
}
?>
