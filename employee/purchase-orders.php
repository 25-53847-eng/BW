<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php', true, 302);
    exit;
}

require_once '../db_config.php';

// For employee, they can only view their own user's POs
$owner_user_id = intval($_SESSION['user_id']);

// Get all POs for this user
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
    <link rel="stylesheet" href="../css/style.css">
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
    </style>
</head>
<body>
    <?php require_once '../sidebar.php'; ?>
    
    <div class="content">
        <?php require_once '../top-nav.php'; ?>
        
        <div class="po-container">
            <div class="po-header">
                <h1 style="color: #f4d03f;"><i class="fas fa-shopping-cart"></i> Purchase Orders</h1>
            </div>

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
</body>
</html>
