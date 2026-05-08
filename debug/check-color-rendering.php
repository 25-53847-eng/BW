<?php
require '../db_config.php';

echo "Sample records with colors and their rendering:\n\n";

$r = $conn->query("SELECT id, item_code, highlight_color FROM delivery_records WHERE highlight_color IS NOT NULL AND highlight_color != '' LIMIT 5");

while ($row = $r->fetch_assoc()) {
    $highlight_color = trim((string) $row['highlight_color']);
    
    // Check if it matches the regex
    if (preg_match('/^#?[0-9a-fA-F]{6}$/', $highlight_color)) {
        echo "? ID: " . $row['id'] . ", Color: $highlight_color => VALID HEX\n";
        
        // Show what RGBA value would be generated
        if ($highlight_color[0] !== '#') {
            $highlight_color = '#' . $highlight_color;
        }
        $hex = ltrim($highlight_color, '#');
        $r_comp = hexdec(substr($hex, 0, 2));
        $g_comp = hexdec(substr($hex, 2, 2));
        $b_comp = hexdec(substr($hex, 4, 2));
        $rgba_soft = sprintf('rgba(%d, %d, %d, 0.18)', $r_comp, $g_comp, $b_comp);
        echo "  RGBA(soft): $rgba_soft\n";
    } else {
        echo "? ID: " . $row['id'] . ", Color: $highlight_color => INVALID HEX (doesn't match regex)\n";
    }
}
?>

