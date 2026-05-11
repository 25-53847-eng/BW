<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// List of missing companies
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

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('2024 to NOW BW Sales Record');
$max_row = $sheet->getHighestRow();

echo "Importing missing companies' records...\n\n";

$inserted_count = 0;
$skipped_count = 0;

foreach ($missing_companies as $company) {
    echo "Processing: $company\n";
    
    // Find all rows with this company
    $company_rows = [];
    for ($row = 2; $row <= $max_row; $row++) {
        $sold_to = trim($sheet->getCell('I' . $row)->getValue());
        if ($sold_to === $company) {
            $company_rows[] = $row;
        }
    }
    
    if (empty($company_rows)) {
        echo "  ❌ No rows found for this company\n";
        $skipped_count++;
        continue;
    }
    
    echo "  Found " . count($company_rows) . " rows\n";
    
    // Insert these rows
    foreach ($company_rows as $row_idx) {
        $invoice_no = trim($sheet->getCell('B' . $row_idx)->getValue());
        $date = trim($sheet->getCell('C' . $row_idx)->getValue());
        $item = trim($sheet->getCell('D' . $row_idx)->getValue());
        $qty = intval($sheet->getCell('F' . $row_idx)->getValue());
        $serial_no = trim($sheet->getCell('H' . $row_idx)->getValue());
        $delivery_date = trim($sheet->getCell('L' . $row_idx)->getValue());
        
        // Skip if no invoice or item
        if (empty($invoice_no) || empty($item)) {
            continue;
        }
        
        // Check if record already exists
        $stmt_check = $conn->prepare("SELECT id FROM delivery_records WHERE invoice_no = ? AND item_code = ? AND company_name = ?");
        $stmt_check->bind_param('sss', $invoice_no, $item, $company);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            // Record already exists, skip
            $stmt_check->close();
            continue;
        }
        $stmt_check->close();
        
        // Insert record
        $stmt_insert = $conn->prepare("INSERT INTO delivery_records 
            (company_name, sold_to, invoice_no, item_code, quantity, serial_no, delivery_date, dataset_name, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        
        $dataset_name = '2024 to NOW BW Sales Record';
        $stmt_insert->bind_param('sssssss', $company, $company, $invoice_no, $item, $serial_no, $delivery_date, $dataset_name);
        $stmt_insert->bind_param('i', $qty);
        
        if ($stmt_insert->execute()) {
            $inserted_count++;
        } else {
            echo "    Error inserting row: " . $stmt_insert->error . "\n";
        }
        $stmt_insert->close();
    }
    
    echo "  ✅ Inserted " . count($company_rows) . " records\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Total inserted: $inserted_count\n";
echo "Total skipped: $skipped_count\n";

// Show new company count
$result_count = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'");
$row_count = $result_count->fetch_assoc();
echo "Total unique companies: " . $row_count['count'] . "\n";
?>
