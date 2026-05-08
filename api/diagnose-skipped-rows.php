<?php
/**
 * Simulate what happens when we upload by checking for common validation issues
 */
header('Content-Type: application/json');

$report = [
    'possible_skip_reasons' => [
        '1' => 'Empty rows (all fields blank)',
        '2' => 'Total/Subtotal rows (contains "TOTAL", "SUBTOTAL", "SUM")',  
        '3' => 'Header-repeat rows (duplicate column headers)',
        '4' => 'Missing critical fields (Item Code, Quantity, etc)',
        '5' => 'Invalid data types',
    ],
    'solution' => 'Need to SEE the actual import response to know what was skipped',
    'action_items' => [
        'Upload the file again',
        'Look at browser console (F12) → Network tab',
        'Find the request to /api/import-data.php',
        'Check Response for: skipped_count, skipped_rows array',
        'Screenshot and send me the response!',
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
