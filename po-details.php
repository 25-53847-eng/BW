<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

require_once 'db_config.php';

function so_id($id) {
    return 'SO-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function identifyGrouping($itemName) {
    $lowerName = strtolower($itemName);

    if (strpos($lowerName, 'multi') !== false ||
        strpos($lowerName, 'quattro') !== false ||
        strpos($lowerName, 'quad') !== false ||
        preg_match('/o2.*lel|lel.*o2/', $lowerName)) {
        return 'Group B - Multi Gas';
    }

    return 'Group A - Single Gas';
}

$orderId = intval($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: inventory.php?tab=orders', true, 302);
    exit;
}

$flash = $_SESSION['po_detail_flash'] ?? null;
unset($_SESSION['po_detail_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_po') {
        $itemCode = trim($_POST['item_code'] ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $poNumber = trim($_POST['po_number'] ?? '');
        $poStatus = trim($_POST['po_status'] ?? 'No PO');
        $orderDate = trim($_POST['order_date'] ?? '');
        $invoiceNo = trim($_POST['invoice_no'] ?? '');
        $pesoCost = floatval($_POST['peso_cost'] ?? 0);
        $foreignCost = floatval($_POST['foreign_cost'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($itemCode === '' || $itemName === '') {
            $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Product code and name are required.'];
            header('Location: po-details.php?id=' . $orderId, true, 302);
            exit;
        }

        $allowedPoStatus = ['No PO', 'Incoming Order', 'Received'];
        if (!in_array($poStatus, $allowedPoStatus, true)) {
            $poStatus = 'No PO';
        }

        // If marking as Received, move to Stock Addition inventory
        if ($poStatus === 'Received') {
            // First update the base fields
            $updateSql = "UPDATE delivery_records
                          SET item_code = ?, item_name = ?, quantity = ?, po_number = ?, invoice_no = ?,
                              peso_cost = ?, foreign_cost = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ? AND company_name IN ('Orders', 'Purchase Order')";
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param('ssissddsi', $itemCode, $itemName, $quantity, $poNumber, $invoiceNo, $pesoCost, $foreignCost, $notes, $orderId);
                if (!$stmt->execute()) {
                    $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Failed to update purchase order.'];
                    $stmt->close();
                    header('Location: po-details.php?id=' . $orderId, true, 302);
                    exit;
                }
                $stmt->close();
            } else {
                $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
                header('Location: po-details.php?id=' . $orderId, true, 302);
                exit;
            }

            // Now move to Stock Addition (handles deduplication)
            $grouping = identifyGrouping($itemName);
            $uom = 'UNITS';
            $now = date('Y-m-d H:i:s');
            $received_status = 'Received';
            
            // Check if item already exists in Stock Addition
            $check_sql = "SELECT id FROM delivery_records WHERE item_code = ? AND company_name = 'Stock Addition' LIMIT 1";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
                header('Location: po-details.php?id=' . $orderId, true, 302);
                exit;
            }
            $check_stmt->bind_param("s", $itemCode);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $item_exists = $check_result->num_rows > 0;
            $check_stmt->close();
            
            if ($item_exists) {
                // Update existing: add to quantity (deduplication)
                $update_inv_sql = "UPDATE delivery_records SET quantity = quantity + ?, updated_at = CURRENT_TIMESTAMP WHERE item_code = ? AND company_name = 'Stock Addition'";
                $upd_stmt = $conn->prepare($update_inv_sql);
                if (!$upd_stmt) {
                    $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
                    header('Location: po-details.php?id=' . $orderId, true, 302);
                    exit;
                }
                $upd_stmt->bind_param("is", $quantity, $itemCode);
                $upd_stmt->execute();
                $upd_stmt->close();
            } else {
                // Create new Stock Addition record
                $company = 'Stock Addition';
                $insert_sql = "INSERT INTO delivery_records (delivery_month, delivery_day, delivery_year, item_code, item_name, quantity, company_name, status, groupings, uom, invoice_no, peso_cost, foreign_cost, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $ins_stmt = $conn->prepare($insert_sql);
                if (!$ins_stmt) {
                    $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
                    header('Location: po-details.php?id=' . $orderId, true, 302);
                    exit;
                }
                $del_month = date('F');
                $del_day = intval(date('j'));
                $del_year = intval(date('Y'));
                $ins_stmt->bind_param("s" . "i" . "i" . "s" . "s" . "i" . "s" . "s" . "s" . "s" . "s" . "d" . "d" . "s" . "s" . "s", $del_month, $del_day, $del_year, $itemCode, $itemName, $quantity, $company, $received_status, $grouping, $uom, $invoiceNo, $pesoCost, $foreignCost, $notes, $now, $now);
                if (!$ins_stmt->execute()) {
                    $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Failed to move to inventory.'];
                    $ins_stmt->close();
                    header('Location: po-details.php?id=' . $orderId, true, 302);
                    exit;
                }
                $ins_stmt->close();
            }
            
            // Delete from Orders/Purchase Order table
            $del_sql = "DELETE FROM delivery_records WHERE id = ? AND company_name IN ('Orders', 'Purchase Order')";
            $del_stmt = $conn->prepare($del_sql);
            if ($del_stmt) {
                $del_stmt->bind_param("i", $orderId);
                $del_stmt->execute();
                $del_stmt->close();
            }
            
            $_SESSION['po_detail_flash'] = ['type' => 'success', 'message' => 'Purchase Order moved to inventory!'];
            header('Location: inventory.php?tab=stock', true, 302);
            exit;
        }

        // For non-Received statuses, just update the PO
        $updateSql = "UPDATE delivery_records
                      SET item_code = ?, item_name = ?, quantity = ?, po_number = ?, invoice_no = ?,
                          po_status = ?, peso_cost = ?, foreign_cost = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                      WHERE id = ? AND company_name IN ('Orders', 'Purchase Order')";

        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            $stmt->bind_param('ssisssddsi', $itemCode, $itemName, $quantity, $poNumber, $invoiceNo, $poStatus, $pesoCost, $foreignCost, $notes, $orderId);
            if ($stmt->execute()) {
                $_SESSION['po_detail_flash'] = ['type' => 'success', 'message' => 'Purchase Order updated successfully.'];
            } else {
                $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Failed to update purchase order.'];
            }
            $stmt->close();
        } else {
            $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
        }

        header('Location: po-details.php?id=' . $orderId, true, 302);
        exit;
    }

    if ($action === 'delete_po') {
        $deleteSql = "DELETE FROM delivery_records WHERE id = ? AND company_name IN ('Orders', 'Purchase Order')";
        $stmt = $conn->prepare($deleteSql);
        if ($stmt) {
            $stmt->bind_param('i', $orderId);
            if ($stmt->execute()) {
                $_SESSION['po_detail_flash'] = ['type' => 'success', 'message' => 'Purchase Order deleted.'];
                header('Location: inventory.php?tab=orders', true, 302);
                exit;
            }
            $stmt->close();
        }
        $_SESSION['po_detail_flash'] = ['type' => 'error', 'message' => 'Failed to delete purchase order.'];
        header('Location: po-details.php?id=' . $orderId, true, 302);
        exit;
    }
}

// Fetch purchase order details
$fetch = $conn->prepare("SELECT * FROM delivery_records WHERE id = ? AND company_name IN ('Orders', 'Purchase Order') LIMIT 1");
if (!$fetch) {
    die('Database error: ' . $conn->error);
}

$fetch->bind_param('i', $orderId);
$fetch->execute();
$result = $fetch->get_result();

if ($result->num_rows === 0) {
    header('Location: inventory.php?tab=orders', true, 302);
    exit;
}

$order = $result->fetch_assoc();
$fetch->close();

$soId = so_id($order['id']);
$viewMode = (isset($_GET['mode']) && $_GET['mode'] === 'view') ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Details - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="navbar-container">
            <div class="navbar-start">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <a href="index.php" style="display:flex;align-items:center;">
                        <img src="assets/logo.png" alt="Andison" style="height:48px;width:auto;object-fit:contain;">
                    </a>
                </div>
            </div>
            <div class="navbar-center">
                <span style="color: #2c3e50; font-size: 18px; font-weight: 600;">Purchase Order Details</span>
            </div>
            <div class="navbar-end">
                <div class="profile-dropdown">
                    <button type="button" class="profile-btn" id="profileBtn" aria-label="Profile menu">
                        <span class="profile-name"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="profileMenu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="help.php"><i class="fas fa-question-circle"></i> Help</a>
                        <hr>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="main-content" id="mainContent">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
            <div>
                <h1 style="margin: 0 0 4px 0; font-size: 28px; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 12px;"><i class="fas fa-file-invoice" style="color: #5bbcff;"></i>Purchase Order <?php echo h($soId); ?></h1>
                <p style="margin: 0; font-size: 13px; color: #7f8c8d; font-weight: 500;">View and manage purchase order details</p>
            </div>
            <a href="inventory.php?tab=orders" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px; background: rgba(96, 168, 255, 0.1); color: #2f7eb3; text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s ease; border: 1px solid rgba(96, 168, 255, 0.2); cursor: pointer;" onmouseover="this.style.background='rgba(96, 168, 255, 0.2)'; this.style.borderColor='rgba(96, 168, 255, 0.4)';" onmouseout="this.style.background='rgba(96, 168, 255, 0.1)'; this.style.borderColor='rgba(96, 168, 255, 0.2)';"><i class="fas fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($flash): ?>
            <div class="flash <?php echo h($flash['type'] ?? 'success'); ?>">
                <?php echo h($flash['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <div style="max-width: 900px; margin: 0 auto;">
            <!-- VIEW MODE -->
            <div id="viewMode" style="<?php echo $viewMode ? 'display: block;' : 'display: none;'; ?> background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; border: 1px solid rgba(229, 231, 235, 0.8); padding: 32px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Reference No.</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px; font-weight: 600;"><?php echo h($soId); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Code</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['item_code']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Name</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['item_name']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Quantity</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo intval($order['quantity']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Status</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['po_status']); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Number</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['po_number']) ?: '—'; ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Reference No.</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['invoice_no']) ?: '—'; ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Order Date</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($order['order_date']) ?: '—'; ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Peso Cost (PHP)</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;">₱<?php echo number_format(floatval($order['peso_cost'] ?? 0), 2); ?></div>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Foreign Cost (USD)</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;">$<?php echo number_format(floatval($order['foreign_cost'] ?? 0), 2); ?></div>
                    </div>
                </div>
                <div style="margin-bottom: 28px;">
                    <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Notes</label>
                    <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px; min-height: 60px; line-height: 1.5;"><?php echo nl2br(h($order['notes'])) ?: '—'; ?></div>
                </div>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="inventory.php?tab=orders" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: #e0e6ed; border: none; color: #2c3e50; font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; transition: all 0.2s ease;" onmouseover="this.style.background='#d0d8e0'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='#e0e6ed'; this.style.transform='translateY(0)';">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <!-- EDIT FORM -->
            <form id="editMode" method="post" style="<?php echo !$viewMode ? 'display: block;' : 'display: none;'; ?> background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; border: 1px solid rgba(229, 231, 235, 0.8); padding: 32px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                <input type="hidden" name="action" value="update_po">

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px;">
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Product Code</label>
                        <input type="text" name="item_code" value="<?php echo h($order['item_code']); ?>" required style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Product Name</label>
                        <input type="text" name="item_name" value="<?php echo h($order['item_name']); ?>" required style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Quantity</label>
                        <input type="number" name="quantity" value="<?php echo intval($order['quantity']); ?>" min="1" required style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">PO Status</label>
                        <select name="po_status" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box; cursor: pointer;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                            <option value="No PO" <?php echo ($order['po_status'] === 'No PO') ? 'selected' : ''; ?>>No PO</option>
                            <option value="Incoming Order" <?php echo ($order['po_status'] === 'Incoming Order') ? 'selected' : ''; ?>>Incoming Order</option>
                            <option value="Received" <?php echo ($order['po_status'] === 'Received') ? 'selected' : ''; ?>>Received</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">PO Number</label>
                        <input type="text" name="po_number" value="<?php echo h($order['po_number']); ?>" placeholder="Optional" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Reference No.</label>
                        <input type="text" name="invoice_no" value="<?php echo h($order['invoice_no']); ?>" placeholder="Optional" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Order Date</label>
                        <input type="date" name="order_date" value="<?php echo h($order['order_date']); ?>" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Peso Cost (PHP)</label>
                        <input type="number" name="peso_cost" value="<?php echo floatval($order['peso_cost'] ?? 0); ?>" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Foreign Cost (USD)</label>
                        <input type="number" name="foreign_cost" value="<?php echo floatval($order['foreign_cost'] ?? 0); ?>" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                    </div>
                </div>

                <div style="margin-bottom: 28px;">
                    <label style="display: block; margin-bottom: 10px; color: #2c3e50; font-weight: 600; font-size: 13px; letter-spacing: 0.3px;">Notes</label>
                    <textarea name="notes" style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #2c3e50; font-size: 14px; font-family: inherit; min-height: 110px; resize: vertical; transition: all 0.2s ease; box-sizing: border-box;" onfocus="this.style.borderColor='#5bbcff'; this.style.boxShadow='0 0 0 3px rgba(91, 188, 255, 0.1)';" onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"><?php echo h($order['notes']); ?></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="submit" id="savePo" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%); border: none; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(91, 188, 255, 0.3);" onmouseover="this.style.boxShadow='0 4px 16px rgba(91, 188, 255, 0.4)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='0 2px 8px rgba(91, 188, 255, 0.3)'; this.style.transform='translateY(0)';">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" id="deletePoBtn" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%); border: none; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);" onmouseover="this.style.boxShadow='0 4px 16px rgba(255, 107, 107, 0.4)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='0 2px 8px rgba(255, 107, 107, 0.3)'; this.style.transform='translateY(0)';">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" onclick="window.location.href='inventory.php?tab=orders'" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: #e0e6ed; border: none; color: #2c3e50; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#d0d8e0'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='#e0e6ed'; this.style.transform='translateY(0)';">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                </div>
            </form>
        </div>

        <style>
            .delete-modal-backdrop { position: fixed; inset: 0; background: rgba(10, 16, 24, 0.72); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
            .delete-modal-backdrop.show { display: flex; }
            .delete-modal-card { width: min(480px, 100%); background: linear-gradient(135deg, #1e2a38, #2a3f5f); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; box-shadow: 0 24px 60px rgba(0,0,0,0.35); padding: 28px; }
            .delete-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
            .delete-modal-title { font-size: 18px; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 10px; }
            .delete-modal-title i { color: #ff6b6b; }
            .delete-modal-message { color: #b5c3d4; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
            .delete-modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
            .delete-modal-btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s ease; }
            .delete-modal-btn.confirm { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%); color: white; box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3); }
            .delete-modal-btn.confirm:hover { box-shadow: 0 4px 16px rgba(255, 107, 107, 0.4); transform: translateY(-2px); }
            .delete-modal-btn.cancel { background: rgba(255,255,255,0.08); color: #d9e4f2; border: 1px solid rgba(255,255,255,0.12); }
            .delete-modal-btn.cancel:hover { background: rgba(255,255,255,0.12); }
            html.light-mode .delete-modal-card,
            body.light-mode .delete-modal-card {
                background: #ffffff;
                border-color: #e5e7eb;
            }
            html.light-mode .delete-modal-title,
            body.light-mode .delete-modal-title {
                color: #1f2937;
            }
            html.light-mode .delete-modal-message,
            body.light-mode .delete-modal-message {
                color: #6b7280;
            }
            html.light-mode .delete-modal-btn.cancel,
            body.light-mode .delete-modal-btn.cancel {
                background: #f3f4f6;
                color: #374151;
                border-color: #d1d5db;
            }
            html.light-mode .delete-modal-btn.cancel:hover,
            body.light-mode .delete-modal-btn.cancel:hover {
                background: #e5e7eb;
            }
        </style>

        <!-- Delete Confirmation Modal -->
        <div class="delete-modal-backdrop" id="deleteConfirmModal">
            <div class="delete-modal-card">
                <div class="delete-modal-header">
                    <h2 class="delete-modal-title"><i class="fas fa-exclamation-circle"></i>Confirm Delete</h2>
                </div>
                <p class="delete-modal-message">Are you sure you want to delete this purchase order? This action cannot be undone.</p>
                <div class="delete-modal-actions">
                    <button type="button" class="delete-modal-btn cancel" id="deleteCancelBtn">Cancel</button>
                    <button type="button" class="delete-modal-btn confirm" id="deleteConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </main>

    <script src="js/app.js" defer></script>
    <script>
        // Delete Modal Handler
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const deletePoBtn = document.getElementById('deletePoBtn');
        const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
        const deleteCancelBtn = document.getElementById('deleteCancelBtn');

        deletePoBtn?.addEventListener('click', function() {
            if (deleteConfirmModal) {
                deleteConfirmModal.classList.add('show');
            }
        });

        deleteCancelBtn?.addEventListener('click', function() {
            if (deleteConfirmModal) {
                deleteConfirmModal.classList.remove('show');
            }
        });

        deleteConfirmBtn?.addEventListener('click', function() {
            const form = document.createElement('form');
            form.method = 'post';
            const action = document.createElement('input');
            action.type = 'hidden';
            action.name = 'action';
            action.value = 'delete_po';
            form.appendChild(action);
            document.body.appendChild(form);
            form.submit();
        });

        // Close modal when clicking outside
        deleteConfirmModal?.addEventListener('click', function(e) {
            if (e.target === deleteConfirmModal) {
                deleteConfirmModal.classList.remove('show');
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        document.getElementById('profileBtn')?.addEventListener('click', function(e) {
            e.preventDefault();
            const menu = document.getElementById('profileMenu');
            if (menu) menu.classList.toggle('active');
        });

        document.addEventListener('click', function(e) {
            const menu = document.getElementById('profileMenu');
            const btn = document.getElementById('profileBtn');
            if (menu && !btn?.contains(e.target)) {
                menu.classList.remove('active');
            }
        });
    </script>
</body>
</html>
