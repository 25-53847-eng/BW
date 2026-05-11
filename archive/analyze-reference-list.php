<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$excel_file = 'BW Gas Detector Current Sales Record.xlsx';
$spreadsheet = IOFactory::load($excel_file);
$sheet = $spreadsheet->getSheetByName('Export List_Sold To');
$max_row = $sheet->getHighestRow();

echo "All entries in Export List_Sold To:\n";
echo "Total rows: " . $max_row . "\n\n";

$all_entries = [];
$for_filtering = [];
$internal_count = 0;

for ($row = 2; $row <= $max_row; $row++) {
    $company = trim($sheet->getCell('A' . $row)->getValue());
    
    if (empty($company)) {
        echo "Row $row: [EMPTY]\n";
        $all_entries[] = "[EMPTY]";
    } elseif ($company === 'Sold To') {
        echo "Row $row: [HEADER]\n";
        $all_entries[] = "[HEADER]";
    } elseif ($company === '(Blanks)') {
        echo "Row $row: [BLANKS]\n";
        $all_entries[] = "[BLANKS]";
    } elseif (in_array($company, ['Andison Manila Use', 'Warranty Replacement', 'Sales record not found', 'to Andison Manila'])) {
        echo "Row $row: $company [INTERNAL]\n";
        $all_entries[] = $company;
        $internal_count++;
    } elseif (strpos($company, 'replaced to') !== false) {
        echo "Row $row: $company [WARRANTY]\n";
        $all_entries[] = $company;
        $internal_count++;
    } else {
        echo "Row $row: $company [CLIENT]\n";
        $all_entries[] = $company;
        $for_filtering[] = $company;
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Total entries: " . count($all_entries) . "\n";
echo "Client companies: " . count($for_filtering) . "\n";
echo "Internal/other: " . $internal_count . "\n";
?>
