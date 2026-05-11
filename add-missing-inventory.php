<?php
require 'vendor/autoload.php';
require 'db_config.php';

// 1. Get all items from Excel with INVENTORY status
$file = 'BW GAS DATA.xlsx';
$spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($file);
$sheet = $spreadsheet->getActiveSheet();

$excelItems = [];
$maxRow = $sheet->getHighestRow();

for ($row = 2; $row <= $maxRow; $row++) {
    $item = $sheet->getCell('A' . $row)->getValue();
    $description = $sheet->getCell('B' . $row)->getValue();
    $qty = $sheet->getCell('C' . $row)->getValue();
    $uom = $sheet->getCell('D' . $row)->getValue();
    $inventory = $sheet->getCell('G' . $row)->getValue();
    
    // Check if marked as INVENTORY
    if (strtoupper(trim($inventory ?? '')) === 'INVENTORY' && !empty($item)) {
        if (!isset($excelItems[$item])) {
            $excelItems[$item] = [
                'description' => $description,
                'qty' => $qty,
                'uom' => $uom
            ];
        }
    }
}

echo "Found " . count($excelItems) . " unique items with INVENTORY status in Excel\n\n";

// 2. Get current inventory items from database
$query = "SELECT item_code FROM inventory";
$result = $conn->query($query);
$dbItems = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $dbItems[$row['item_code']] = true;
    }
}

echo "Found " . count($dbItems) . " items in database inventory\n\n";

// 3. Find missing items
$missingItems = [];
foreach ($excelItems as $item => $data) {
    if (!isset($dbItems[$item])) {
        $missingItems[$item] = $data;
    }
}

echo "Missing items to add: " . count($missingItems) . "\n\n";

if (count($missingItems) > 0) {
    echo "Adding missing items...\n";
    $added = 0;
    $failed = 0;
    
    foreach ($missingItems as $item => $data) {
        $item_name = $conn->real_escape_string($data['description'] ?? '');
        $qty = intval($data['qty'] ?? 0);
        
        $insertQuery = "INSERT INTO inventory (item_code, item_name, quantity) 
                       VALUES ('$item', '$item_name', $qty)
                       ON DUPLICATE KEY UPDATE quantity = $qty";
        
        if ($conn->query($insertQuery)) {
            $added++;
            echo "✓ Added/Updated: $item\n";
        } else {
            $failed++;
            echo "✗ Failed: $item - " . $conn->error . "\n";
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Successfully added: $added\n";
    echo "Failed: $failed\n";
} else {
    echo "All items are already in the database!\n";
}

$conn->close();
?>
