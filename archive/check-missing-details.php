<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// Real missing companies (exclude internal/warranty ones)
$real_missing = [
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

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('2024 to NOW BW Sales Record');
$max_row = $sheet->getHighestRow();

echo "Checking for missing companies' records...\n\n";

foreach ($real_missing as $company) {
    // Find first row with this company in Excel
    $excel_row = null;
    for ($row = 2; $row <= $max_row; $row++) {
        if (trim($sheet->getCell('I' . $row)->getValue()) === $company) {
            $excel_row = $row;
            break;
        }
    }
    
    if ($excel_row) {
        $invoice = trim($sheet->getCell('B' . $excel_row)->getValue());
        $item = trim($sheet->getCell('D' . $excel_row)->getValue());
        
        // Check if this invoice+item exists in database
        $stmt = $conn->prepare("SELECT company_name, COUNT(*) as count FROM delivery_records 
            WHERE invoice_no = ? AND item_code = ? 
            GROUP BY company_name");
        $stmt->bind_param('ss', $invoice, $item);
        $stmt->execute();
        $result = $stmt->get_result();
        $found = false;
        
        while ($row_db = $result->fetch_assoc()) {
            if ($row_db['company_name'] === $company) {
                echo "[FOUND] $company (invoice: $invoice, item: $item)\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            // Check if record exists with different company_name
            $stmt2 = $conn->prepare("SELECT DISTINCT company_name FROM delivery_records 
                WHERE invoice_no = ? AND item_code = ? LIMIT 5");
            $stmt2->bind_param('ss', $invoice, $item);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $other_names = [];
            while ($row2 = $result2->fetch_assoc()) {
                $other_names[] = $row2['company_name'];
            }
            
            if (!empty($other_names)) {
                echo "[DIFFERENT NAME] $company (invoice: $invoice, item: $item) found as: " . implode(', ', $other_names) . "\n";
            } else {
                echo "[MISSING] $company (invoice: $invoice, item: $item) - NOT IN DATABASE\n";
            }
        }
        $stmt->close();
    }
}
?>
