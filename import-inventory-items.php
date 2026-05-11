<?php
require 'vendor/autoload.php';
require 'db_config.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

try {
    $file = 'BW GAS DATA.xlsx';
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    
    // Get headers
    $headers = [];
    for ($col = 1; $col <= 20; $col++) {
        $cell = $sheet->getCellByColumnAndRow($col, 1);
        $value = $cell->getValue();
        if (empty($value)) break;
        $headers[$col] = $value;
    }
    
    // Find column indices (case-insensitive search)
    $item_col = null;
    $desc_col = null;
    $qty_col = null;
    $uom_col = null;
    $serial_col = null;
    $inventory_col = null;
    
    foreach ($headers as $col => $header) {
        $h = strtolower(trim($header));
        if ($h === 'item' || $h === 'item code' || $h === 'itemcode') $item_col = $col;
        if ($h === 'description' || $h === 'desc') $desc_col = $col;
        if ($h === 'qty.' || $h === 'qty' || $h === 'quantity') $qty_col = $col;
        if ($h === 'uom' || $h === 'unit of measure') $uom_col = $col;
        if ($h === 'serial no.' || $h === 'serial no' || $h === 'serial_no') $serial_col = $col;
        if ($h === 'inventory' || $h === 'inventory status' || $h === 'marking') $inventory_col = $col;
    }
    
    echo "Column indices: Item=$item_col, Desc=$desc_col, Qty=$qty_col, UOM=$uom_col, Serial=$serial_col, Inventory=$inventory_col\n\n";
    
    // Get existing items from DB (both delivery_records and warranty_replacements)
    $existing_inventory = [];
    $existing_warranty = [];
    
    $result = $conn->query("SELECT CONCAT(item_code, '-', COALESCE(serial_no, '')) as `key` FROM delivery_records WHERE company_name = 'Stock Addition'");
    while ($row = $result->fetch_assoc()) {
        $existing_inventory[$row['key']] = true;
    }
    
    $result = $conn->query("SELECT CONCAT(item_code, '-', COALESCE(serial_no, '')) as `key` FROM warranty_replacements");
    while ($row = $result->fetch_assoc()) {
        $existing_warranty[$row['key']] = true;
    }
    
    echo "Found " . count($existing_inventory) . " existing inventory items in DB\n";
    echo "Found " . count($existing_warranty) . " existing warranty items in DB\n\n";
    
    // Process all rows - no strict filtering
    $added_inventory = 0;
    $added_warranty = 0;
    $skipped = 0;
    
    for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
        $item_code = $sheet->getCellByColumnAndRow($item_col, $row)->getValue();
        $description = $sheet->getCellByColumnAndRow($desc_col, $row)->getValue();
        $qty = $sheet->getCellByColumnAndRow($qty_col, $row)->getValue();
        $uom = $sheet->getCellByColumnAndRow($uom_col, $row)->getValue();
        $serial_no = $sheet->getCellByColumnAndRow($serial_col, $row)->getValue();
        $inventory_mark = $sheet->getCellByColumnAndRow($inventory_col, $row)->getValue();
        
        // Skip completely empty rows
        if (empty($item_code) && empty($description)) {
            continue;
        }
        
        // Skip header rows
        if (strtolower(trim($item_code)) === 'item' && strtolower(trim($description)) === 'description') {
            continue;
        }
        
        // If item_code is empty, use a placeholder
        if (empty($item_code)) {
            $item_code = 'UNKNOWN-' . uniqid();
        }
        
        if (empty($description)) {
            $description = $item_code;
        }
        
        // Determine routing based on inventory_mark
        $inventory_mark_lower = strtolower(trim((string)$inventory_mark));
        $is_warranty = false;
        $is_inventory = false;
        
        if (strpos($inventory_mark_lower, 'warranty') !== false) {
            $is_warranty = true;
        } elseif (strpos($inventory_mark_lower, 'inventory') !== false || 
                  strpos($inventory_mark_lower, 'stock') !== false) {
            $is_inventory = true;
        } else {
            // Default: if no marking or unclear, route to inventory
            $is_inventory = true;
        }
        
        $key = $item_code . '-' . ($serial_no ?? '');
        
        // ROUTE TO WARRANTY REPLACEMENTS
        if ($is_warranty) {
            if (!isset($existing_warranty[$key])) {
                $stmt = $conn->prepare("INSERT INTO warranty_replacements (item_code, item_name, quantity, uom, serial_no, company_name, status, warranty_date, record_date) VALUES (?, ?, ?, ?, ?, 'Stock Addition', 'Warranty Pending', NOW(), NOW())");
                $stmt->bind_param("ssdss", $item_code, $description, $qty, $uom, $serial_no);
                
                if ($stmt->execute()) {
                    echo "✅ WARRANTY: Item=$item_code, Qty=$qty, Serial=$serial_no\n";
                    $added_warranty++;
                    $existing_warranty[$key] = true;
                } else {
                    echo "❌ ERROR adding WARRANTY $item_code: " . $stmt->error . "\n";
                }
                $stmt->close();
            } else {
                echo "⚠️  WARRANTY EXISTS: Item=$item_code, Qty=$qty, Serial=$serial_no\n";
            }
        }
        // ROUTE TO INVENTORY (delivery_records with Stock Addition)
        else {
            if (!isset($existing_inventory[$key])) {
                $stmt = $conn->prepare("INSERT INTO delivery_records (item_code, item_name, quantity, uom, serial_no, company_name, status, delivery_date, record_date) VALUES (?, ?, ?, ?, ?, 'Stock Addition', 'Delivered', NOW(), NOW())");
                $stmt->bind_param("ssdss", $item_code, $description, $qty, $uom, $serial_no);
                
                if ($stmt->execute()) {
                    echo "✅ INVENTORY: Item=$item_code, Qty=$qty, Serial=$serial_no\n";
                    $added_inventory++;
                    $existing_inventory[$key] = true;
                } else {
                    echo "❌ ERROR adding INVENTORY $item_code: " . $stmt->error . "\n";
                }
                $stmt->close();
            } else {
                echo "⚠️  INVENTORY EXISTS: Item=$item_code, Qty=$qty, Serial=$serial_no\n";
            }
        }
    }
    
    echo "\n========================================\n";
    echo "✅ Total added to INVENTORY: $added_inventory items\n";
    echo "✅ Total added to WARRANTY: $added_warranty items\n";
    echo "Total inventory items now: " . count($existing_inventory) . "\n";
    echo "Total warranty items now: " . count($existing_warranty) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
    echo "Total added: $added_count items\n";
    echo "Total in inventory now: " . count($existing) . " items\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
