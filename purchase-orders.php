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
    
    if ($action === 'mark_received') {
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
            font-family: 'Poppins', sans-serif;
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

    <script>
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
            alert('Feature coming soon! Create POs through admin panel.');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('markReceivedModal');
            if (event.target === modal) {
                closeMarkReceivedModal();
            }
        }
    </script>
</body>
</html>
