<?php
require '../db_config.php';

function inferCategory($itemName, $itemCode) {
    $lower = strtolower($itemName . ' ' . $itemCode);
    
    // BW KNIT devices (usually have model like "BW__" with H2S/LEL indicators)
    if (preg_match('/bw\s*(solo|quattro|clip|knit|gas|alert)/i', $itemName)) {
        if (preg_match('/serial|serial\s*no|serial no/i', $itemName)) {
            return '1a';
        }
        // Default BW to 1a if has serial indicators in code
        if (preg_match('/\b(bwc|bws|mcu|hwu)/i', $itemCode)) {
            return '1a';
        }
        return '1b'; // BW accessories
    }
    
    // RAE devices
    if (preg_match('/rae\s*(ultra|pro|monitor|system)/i', $itemName)) {
        if (preg_match('/serial|serial\s*no|unit|display/i', $itemName)) {
            return '2a';
        }
        return '2a'; // RAE units default to 2a
    }
    
    // Accessories
    if (preg_match('/(replacement|filter|hose|cable|charger|battery|adapter|screen|shell|enclosure|pcb|inlet|boot|clip|screw|gasket|kit|ring|probe|sensor)/i', $itemName)) {
        // Distinguish BW accessories from RAE
        if (preg_match('/bw|solo|flex|clip/i', $itemName)) {
            return '1b';
        }
        if (preg_match('/rae|ultra|pro/i', $itemName)) {
            return '2b';
        }
        return '1b'; // Default accessories to BW
    }
    
    // Warranty/Replacement
    if (preg_match('/(warranty|replacement|exchange|swap)/i', $itemName)) {
        return '3a';
    }
    
    // Calibration gas
    if (preg_match('/(calibration|cal|gas|cylinder|ppm|h2s|co|o2|lel)/i', $itemName)) {
        if (preg_match('/(regulator|gauge|fitting|connector)/i', $itemName)) {
            return '4a';
        }
        return '4a'; // Calibration gas & equipment
    }
    
    // Default
    return '';
}

echo "Updating groupings for empty records...\n\n";

// Get all records with empty groupings that have sold_to
$r = $conn->query("SELECT id, item_code, item_name FROM delivery_records 
WHERE (groupings IS NULL OR groupings = '') 
AND COALESCE(sold_to, '') != ''
AND company_name NOT IN ('Orders', 'Inquiry', 'Stock Addition')
ORDER BY id");

$updated = 0;
$failed = 0;

while ($row = $r->fetch_assoc()) {
    $category = inferCategory($row['item_name'], $row['item_code']);
    
    if (!empty($category)) {
        $item_id = intval($row['id']);
        $category_safe = $conn->real_escape_string($category);
        
        if ($conn->query("UPDATE delivery_records SET groupings = '$category_safe' WHERE id = $item_id")) {
            $updated++;
            echo "? ID: " . $row['id'] . " | " . $row['item_code'] . " => $category\n";
        } else {
            $failed++;
            echo "? Failed: ID " . $row['id'] . "\n";
        }
    }
}

echo "\n? Updated: $updated records\n";
echo "? Failed: $failed records\n";

// Show summary of groupings now
echo "\n\nFinal groupings distribution (WITH sold_to):\n\n";
$r_final = $conn->query("SELECT groupings, COUNT(*) as cnt FROM delivery_records 
WHERE COALESCE(sold_to, '') != '' 
AND company_name NOT IN ('Orders', 'Inquiry', 'Stock Addition')
GROUP BY groupings
ORDER BY cnt DESC");

while ($row = $r_final->fetch_assoc()) {
    $grouping_display = empty($row['groupings']) ? '(EMPTY)' : $row['groupings'];
    echo "- $grouping_display: " . $row['cnt'] . " records\n";
}
?>


