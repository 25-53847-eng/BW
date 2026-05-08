<?php
require_once 'db_config.php';
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');
$max_row = $sheet->getHighestRow();

// Get all client companies from reference list
$reference_clients = [];
for ($row = 2; $row <= $max_row; $row++) {
    $company = trim($sheet->getCell('A' . $row)->getValue());
    
    if (empty($company) || $company === 'Sold To' || $company === '(Blanks)') continue;
    if (strpos($company, 'replaced to') !== false) continue;
    if (in_array($company, ['Andison Manila Use', 'Warranty Replacement', 'Sales record not found', 'to Andison Manila'])) continue;
    
    $reference_clients[] = $company;
}

sort($reference_clients);

// Check which ones have delivery records
$no_records = [];
foreach ($reference_clients as $company) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM delivery_records WHERE company_name = ?");
    $stmt->bind_param('s', $company);
    $stmt->execute();
    $result = $stmt->get_result();
    $row_data = $result->fetch_assoc();
    $stmt->close();
    
    if ($row_data['count'] == 0) {
        $no_records[] = $company;
    }
}

echo "Reference client companies: " . count($reference_clients) . "\n";
echo "With delivery records: " . (count($reference_clients) - count($no_records)) . "\n";
echo "Without delivery records: " . count($no_records) . "\n\n";

if (!empty($no_records)) {
    echo "Companies with NO delivery records:\n";
    foreach ($no_records as $i => $comp) {
        echo ($i + 1) . ". " . $comp . "\n";
    }
}
?>
