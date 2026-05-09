<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

require_once 'db_config.php';

// Check permission
if (!isPermissionEnabled('view_purchase_orders', $conn)) {
    header('Location: index.php', true, 302);
    exit;
}

$owner_user_id = intval($_SESSION['user_id']);

// Handle PO status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $po_id = intval($_POST['po_id'] ?? 0);
    
    if ($action === 'create_po') {
        // Create new purchase order
        $item_code = trim($_POST['item_code'] ?? '');
        $item_name = trim($_POST['item_name'] ?? '');
        $quantity_ordered = intval($_POST['quantity_ordered'] ?? 0);
        $expected_delivery_date = trim($_POST['expected_delivery_date'] ?? '');
        
        if (!$item_code || !$item_name || $quantity_ordered <= 0 || !$expected_delivery_date) {
            $_SESSION['error_msg'] = "Invalid purchase order data.";
            header('Location: purchase-orders.php', true, 302);
            exit;
        }
        
        // Generate PO number
        $po_number = 'PO-' . date('Ymd') . '-' . random_int(1000, 9999);
        
        $insert_stmt = $conn->prepare("
            INSERT INTO purchase_orders 
            (owner_user_id, po_number, item_code, item_name, quantity_ordered, expected_delivery_date, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        
        $insert_stmt->bind_param('isssds', $owner_user_id, $po_number, $item_code, $item_name, $quantity_ordered, $expected_delivery_date);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success_msg'] = "Purchase order created successfully! PO# " . $po_number;
            header('Location: purchase-orders.php', true, 302);
            exit;
        } else {
            $_SESSION['error_msg'] = "Failed to create purchase order: " . $insert_stmt->error;
            header('Location: purchase-orders.php', true, 302);
            exit;
        }
    } elseif ($action === 'mark_received') {
        $quantity_received = intval($_POST['quantity_received'] ?? 0);
        
        // Update PO status to received
        $update_stmt = $conn->prepare("
            UPDATE purchase_orders 
            SET status = 'received', 
                quantity_received = ?,
                actual_received_date = CURDATE()
            WHERE id = ? AND owner_user_id = ?
        ");
        $update_stmt->bind_param('iii', $quantity_received, $po_id, $owner_user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_msg'] = "PO marked as received! Inventory automatically updated.";
            header('Location: purchase-orders.php', true, 302);
            exit;
        }
    } elseif ($action === 'delete') {
        $delete_stmt = $conn->prepare("DELETE FROM purchase_orders WHERE id = ? AND owner_user_id = ? AND status != 'received'");
        $delete_stmt->bind_param('ii', $po_id, $owner_user_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success_msg'] = "Purchase order deleted.";
            header('Location: purchase-orders.php', true, 302);
            exit;
        }
    }
}

// Get all POs
$pos_result = $conn->query("
    SELECT * FROM purchase_orders 
    WHERE owner_user_id = $owner_user_id
    ORDER BY created_at DESC
");

$pending = $received = [];
while ($row = $pos_result->fetch_assoc()) {
    if ($row['status'] === 'received') {
        $received[] = $row;
    } else {
        $pending[] = $row;
    }
}

// Get all unique products for dropdown
$items_result = $conn->query("
    SELECT DISTINCT item_code, item_name 
    FROM delivery_records 
    WHERE item_code IS NOT NULL AND item_code != ''
    ORDER BY item_code ASC
");

$products = [];
while ($row = $items_result->fetch_assoc()) {
    $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .po-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .po-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .po-section {
            background: linear-gradient(135deg, #1e2a38 0%, #2a3f5f 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .po-section h3 {
            color: #f4d03f;
            margin-bottom: 20px;
            font-size: 18px;
        }
        .po-table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-table thead {
            background: rgba(244, 208, 63, 0.1);
        }
        .po-table th {
            padding: 12px;
            text-align: left;
            color: #f4d03f;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .po-table td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #c0d0e0;
        }
        .po-table tr:hover {
            background: rgba(244, 208, 63, 0.05);
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending {
            background: #ff9800;
            color: #fff;
        }
        .status-received {
            background: #2ecc71;
            color: #fff;
        }
        .btn-mark-received {
            background: #4a90e2;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .btn-mark-received:hover {
            background: #357abd;
        }
        .btn-delete {
            background: #ff6b6b;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: linear-gradient(135deg, #1e2a38 0%, #2a3f5f 100%);
            border-radius: 15px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .modal-content h3 {
            color: #f4d03f;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            color: #a0b4c8;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-family: Verdana, sans-serif;
        }
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-family: Verdana, sans-serif;
        }
        .form-group select option {
            background: #1e2a38;
            color: #fff;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
        }
        .btn-submit {
            background: #2ecc71;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-cancel {
            background: #555;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php require_once 'sidebar.php'; ?>
    
    <div class="content">
        <?php require_once 'top-nav.php'; ?>
        
        <div class="po-container">
            <div class="po-header">
                <h1 style="color: #f4d03f;"><i class="fas fa-shopping-cart"></i> Purchase Orders</h1>
                <button onclick="openNewPOModal()" class="btn-primary" style="background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);">
                    <i class="fas fa-plus"></i> New Purchase Order
                </button>
            </div>

            <?php if (isset($_SESSION['success_msg'])): ?>
                <div style="background: rgba(46, 204, 113, 0.15); border: 1px solid #2ecc71; padding: 12px; border-radius: 8px; color: #2ecc71; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?= $_SESSION['success_msg'] ?>
                </div>
                <?php unset($_SESSION['success_msg']); ?>
            <?php endif; ?>

            <!-- Pending POs -->
            <div class="po-section">
                <h3><i class="fas fa-clock"></i> Pending Orders (<?= count($pending) ?>)</h3>
                <?php if (empty($pending)): ?>
                    <p style="color: #8a9ab5; text-align: center; padding: 20px;">No pending purchase orders</p>
                <?php else: ?>
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th>PO#</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Qty Ordered</th>
                                <th>Expected Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $po): ?>
                                <tr>
                                    <td><?= htmlspecialchars($po['po_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($po['item_code']) ?></td>
                                    <td><?= htmlspecialchars($po['item_name']) ?></td>
                                    <td><?= $po['quantity_ordered'] ?></td>
                                    <td><?= $po['expected_delivery_date'] ?></td>
                                    <td><span class="status-badge status-pending"><?= ucfirst($po['status']) ?></span></td>
                                    <td>
                                        <button onclick="openMarkReceivedModal(<?= $po['id'] ?>, '<?= addslashes($po['item_code']) ?>')" class="btn-mark-received">
                                            <i class="fas fa-check"></i> Receive
                                        </button>
                                        <button onclick="deletePO(<?= $po['id'] ?>)" class="btn-delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Received POs -->
            <div class="po-section">
                <h3><i class="fas fa-check-circle"></i> Received Orders (<?= count($received) ?>)</h3>
                <?php if (empty($received)): ?>
                    <p style="color: #8a9ab5; text-align: center; padding: 20px;">No received purchase orders</p>
                <?php else: ?>
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th>PO#</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Qty Ordered</th>
                                <th>Qty Received</th>
                                <th>Received Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($received as $po): ?>
                                <tr>
                                    <td><?= htmlspecialchars($po['po_number'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($po['item_code']) ?></td>
                                    <td><?= htmlspecialchars($po['item_name']) ?></td>
                                    <td><?= $po['quantity_ordered'] ?></td>
                                    <td><?= $po['quantity_received'] ?></td>
                                    <td><?= $po['actual_received_date'] ?></td>
                                    <td><span class="status-badge status-received"><i class="fas fa-check"></i> Received</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mark as Received Modal -->
    <div id="markReceivedModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-check-circle"></i> Mark as Received</h3>
            <form onsubmit="submitMarkReceived(event)">
                <input type="hidden" id="poId" name="po_id">
                <div class="form-group">
                    <label>Item Code</label>
                    <input type="text" id="itemCodeDisplay" readonly>
                </div>
                <div class="form-group">
                    <label>Quantity Received</label>
                    <input type="number" id="quantityReceived" name="quantity_received" min="1" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeMarkReceivedModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Mark Received</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create New PO Modal -->
    <div id="newPOModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h3><i class="fas fa-plus-circle"></i> Create Purchase Order</h3>
            <form onsubmit="submitNewPO(event)">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Product Code</label>
                        <select id="productCode" name="product_code" required onchange="syncProductName()">
                            <option value="">-- Select Product Code --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Product Name</label>
                        <select id="productName" name="product_name" required onchange="syncProductCode()">
                            <option value="">-- Select Product Name --</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" id="poQuantity" name="quantity" min="1" placeholder="e.g., 100" required>
                    </div>
                    <div class="form-group">
                        <label>Expected Delivery Date</label>
                        <input type="date" id="poDeliveryDate" name="expected_delivery_date" required>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeNewPOModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Create PO</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Products data from PHP
        const products = <?php echo json_encode($products); ?>;
        
        // Create a map for quick lookups
        const codeToName = {};
        const nameToCode = {};
        products.forEach(p => {
            codeToName[p.item_code] = p.item_name;
            nameToCode[p.item_name] = p.item_code;
        });

        function openMarkReceivedModal(poId, itemCode) {
            document.getElementById('poId').value = poId;
            document.getElementById('itemCodeDisplay').value = itemCode;
            document.getElementById('quantityReceived').value = '';
            document.getElementById('markReceivedModal').classList.add('show');
        }

        function closeMarkReceivedModal() {
            document.getElementById('markReceivedModal').classList.remove('show');
        }

        function submitMarkReceived(event) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'mark_received');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => {
                window.location.reload();
            });
        }

        function deletePO(poId) {
            if (!confirm('Delete this purchase order?')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('po_id', poId);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(() => {
                window.location.reload();
            });
        }

        function openNewPOModal() {
            document.getElementById('newPOModal').classList.add('show');
            // Reset form
            document.getElementById('productCode').value = '';
            document.getElementById('productName').value = '';
            document.getElementById('poQuantity').value = '';
            document.getElementById('poDeliveryDate').value = '';
            
            // Load products into dropdowns
            loadProducts();
        }

        function closeNewPOModal() {
            document.getElementById('newPOModal').classList.remove('show');
        }

        function loadProducts() {
            const codeSelect = document.getElementById('productCode');
            const nameSelect = document.getElementById('productName');
            
            // Clear existing options (keep placeholder)
            codeSelect.innerHTML = '<option value="">-- Select Product Code --</option>';
            nameSelect.innerHTML = '<option value="">-- Select Product Name --</option>';
            
            // Add product options
            products.forEach(p => {
                const codeOption = document.createElement('option');
                codeOption.value = p.item_code;
                codeOption.textContent = p.item_code;
                codeSelect.appendChild(codeOption);
                
                const nameOption = document.createElement('option');
                nameOption.value = p.item_name;
                nameOption.textContent = p.item_name;
                nameSelect.appendChild(nameOption);
            });
        }

        function syncProductCode() {
            const selectedName = document.getElementById('productName').value;
            if (selectedName && nameToCode[selectedName]) {
                document.getElementById('productCode').value = nameToCode[selectedName];
            }
        }

        function syncProductName() {
            const selectedCode = document.getElementById('productCode').value;
            if (selectedCode && codeToName[selectedCode]) {
                document.getElementById('productName').value = codeToName[selectedCode];
            }
        }

        function submitNewPO(event) {
            event.preventDefault();
            
            const productCode = document.getElementById('productCode').value;
            const productName = document.getElementById('productName').value;
            const quantity = document.getElementById('poQuantity').value;
            const deliveryDate = document.getElementById('poDeliveryDate').value;
            
            if (!productCode || !productName || !quantity || !deliveryDate) {
                alert('Please fill in all fields');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'create_po');
            formData.append('item_code', productCode);
            formData.append('item_name', productName);
            formData.append('quantity_ordered', quantity);
            formData.append('expected_delivery_date', deliveryDate);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            }).then(response => response.text())
            .then(text => {
                window.location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create purchase order');
            });
        }

        window.onclick = function(event) {
            const markModal = document.getElementById('markReceivedModal');
            const poModal = document.getElementById('newPOModal');
            if (event.target === markModal) {
                closeMarkReceivedModal();
            }
            if (event.target === poModal) {
                closeNewPOModal();
            }
        }
    </script>
</body>
</html>
