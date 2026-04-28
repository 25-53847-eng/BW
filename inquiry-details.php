<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

require_once 'db_config.php';

function inq_id($id) {
    return 'INQ-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$inquiryId = intval($_GET['id'] ?? 0);
if ($inquiryId <= 0) {
    header('Location: inquiry.php', true, 302);
    exit;
}

$flash = $_SESSION['inquiry_detail_flash'] ?? null;
unset($_SESSION['inquiry_detail_flash']);

// Fetch inquiry record
$fetch = $conn->prepare("SELECT * FROM delivery_records WHERE id = ? AND company_name = 'Inquiry' LIMIT 1");
if (!$fetch) {
    die('Database error: ' . $conn->error);
}
$fetch->bind_param('i', $inquiryId);
$fetch->execute();
$result = $fetch->get_result();

if ($result->num_rows === 0) {
    header('Location: inquiry.php', true, 302);
    exit;
}

$inquiry = $result->fetch_assoc();
$fetch->close();

$inquiryRefId = inq_id($inquiry['id']);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_inquiry') {
        $customer = trim($_POST['customer'] ?? '');
        $orderDate = trim($_POST['order_date'] ?? '');
        $itemCode = trim($_POST['item_code'] ?? '');
        $itemName = trim($_POST['item_name'] ?? '');
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $unitPrice = floatval($_POST['unit_price'] ?? 0);
        $poNumber = trim($_POST['po_number'] ?? '');
        $poStatus = trim($_POST['po_status'] ?? 'No PO');
        $notes = trim($_POST['notes'] ?? '');

        if ($customer === '' || $orderDate === '' || $itemCode === '' || $itemName === '') {
            $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Client, date, product code, and product name are required.'];
            header('Location: inquiry-details.php?id=' . $inquiryId, true, 302);
            exit;
        }

        $allowedPoStatus = ['No PO', 'Pending', 'Received'];
        if (!in_array($poStatus, $allowedPoStatus, true)) {
            $poStatus = 'No PO';
        }

        if ($poStatus === 'Received' && $poNumber === '') {
            $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'PO Number is required before setting PO Status to Received.'];
            header('Location: inquiry-details.php?id=' . $inquiryId, true, 302);
            exit;
        }

        $dt = DateTime::createFromFormat('Y-m-d', $orderDate);
        if (!$dt) {
            $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Invalid inquiry date format.'];
            header('Location: inquiry-details.php?id=' . $inquiryId, true, 302);
            exit;
        }

        $deliveryMonth = $dt->format('F');
        $deliveryDay = intval($dt->format('j'));
        $deliveryYear = intval($dt->format('Y'));
        $deliveryDate = $dt->format('Y-m-d');
        $totalAmount = $quantity * $unitPrice;

        // If status is Received, move to Delivery Records
        if ($poStatus === 'Received') {
            $newCompany = 'Delivery Records';
            $newStatus = 'Ready for Delivery';
            
            // Update inquiry record to move to Delivery Records
            $updateSql = "UPDATE delivery_records
                          SET order_customer = ?, order_date = ?, item_code = ?, item_name = ?,
                              quantity = ?, unit_price = ?, total_amount = ?, po_number = ?,
                              po_status = ?, notes = ?, delivery_month = ?,
                              delivery_day = ?, delivery_year = ?, delivery_date = ?,
                              company_name = ?, status = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ? AND company_name = 'Inquiry'";
            
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param('ssssiddssssiisssi', $customer, $orderDate, $itemCode, $itemName, $quantity, $unitPrice, $totalAmount, $poNumber, $poStatus, $notes, $deliveryMonth, $deliveryDay, $deliveryYear, $deliveryDate, $newCompany, $newStatus, $inquiryId);
                if ($stmt->execute()) {
                    $_SESSION['inquiry_detail_flash'] = ['type' => 'success', 'message' => 'Inquiry moved to Delivery Records!'];
                    $stmt->close();
                    header('Location: delivery-records.php', true, 302);
                    exit;
                } else {
                    $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Failed to move inquiry to delivery records.'];
                }
                $stmt->close();
            } else {
                $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
            }
        } else {
            // Keep in Inquiry, just update fields
            $updateSql = "UPDATE delivery_records
                          SET order_customer = ?, order_date = ?, item_code = ?, item_name = ?,
                              quantity = ?, unit_price = ?, total_amount = ?, po_number = ?,
                              po_status = ?, notes = ?, delivery_month = ?,
                              delivery_day = ?, delivery_year = ?, delivery_date = ?, updated_at = CURRENT_TIMESTAMP
                          WHERE id = ? AND company_name = 'Inquiry'";
            
            $stmt = $conn->prepare($updateSql);
            if ($stmt) {
                $stmt->bind_param('ssssiddssssiisi', $customer, $orderDate, $itemCode, $itemName, $quantity, $unitPrice, $totalAmount, $poNumber, $poStatus, $notes, $deliveryMonth, $deliveryDay, $deliveryYear, $deliveryDate, $inquiryId);
                if ($stmt->execute()) {
                    $_SESSION['inquiry_detail_flash'] = ['type' => 'success', 'message' => 'Inquiry updated successfully.'];
                } else {
                    $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Failed to update inquiry.'];
                }
                $stmt->close();
            } else {
                $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Database error.'];
            }
        }

        header('Location: inquiry-details.php?id=' . $inquiryId, true, 302);
        exit;
    }

    if ($action === 'delete_inquiry') {
        $deleteSql = "DELETE FROM delivery_records WHERE id = ? AND company_name = 'Inquiry'";
        $stmt = $conn->prepare($deleteSql);
        if ($stmt) {
            $stmt->bind_param('i', $inquiryId);
            if ($stmt->execute()) {
                $_SESSION['inquiry_detail_flash'] = ['type' => 'success', 'message' => 'Inquiry deleted.'];
                $stmt->close();
                header('Location: inquiry.php', true, 302);
                exit;
            }
            $stmt->close();
        }
        $_SESSION['inquiry_detail_flash'] = ['type' => 'error', 'message' => 'Failed to delete inquiry.'];
        header('Location: inquiry-details.php?id=' . $inquiryId, true, 302);
        exit;
    }
}

$autoEditMode = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Details - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
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
            <div class="navbar-center" style="flex: 1; text-align: center;">
                <span style="color: #2c3e50; font-size: 18px; font-weight: 600;">Inquiry Details</span>
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
                        <a href="logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <?php require __DIR__ . '/sidebar.php'; ?>

        <main class="main-content" id="mainContent">
            <div style="max-width: 900px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 24px;">
                    <div>
                        <h1 style="margin: 0 0 4px 0; font-size: 28px; font-weight: 700; color: #2c3e50; display: flex; align-items: center; gap: 12px;"><i class="fas fa-file-invoice" style="color: #5bbcff;"></i>Inquiry <?php echo h($inquiryRefId); ?></h1>
                        <p style="margin: 0; font-size: 13px; color: #7f8c8d; font-weight: 500;">View and manage inquiry details</p>
                    </div>
                    <a href="inquiry.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 8px; background: rgba(96, 168, 255, 0.1); color: #2f7eb3; text-decoration: none; font-weight: 600; font-size: 13px; transition: all 0.2s ease; border: 1px solid rgba(96, 168, 255, 0.2); cursor: pointer;" onmouseover="this.style.background='rgba(96, 168, 255, 0.2)'; this.style.borderColor='rgba(96, 168, 255, 0.4)';" onmouseout="this.style.background='rgba(96, 168, 255, 0.1)'; this.style.borderColor='rgba(96, 168, 255, 0.2)';"><i class="fas fa-arrow-left"></i> Back</a>
                </div>

                <?php if ($flash): ?>
                    <div style="padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; font-weight: 500; background: <?php echo $flash['type'] === 'success' ? 'rgba(46, 204, 113, 0.15)' : 'rgba(231, 76, 60, 0.16)'; ?>; color: <?php echo $flash['type'] === 'success' ? '#7dffb0' : '#ffb5ad'; ?>; border: 1px solid <?php echo $flash['type'] === 'success' ? 'rgba(46, 204, 113, 0.25)' : 'rgba(231, 76, 60, 0.28)'; ?>;">
                        <?php echo h($flash['message'] ?? ''); ?>
                    </div>
                <?php endif; ?>

                <!-- VIEW MODE -->
                <div id="viewMode" style="<?php echo $autoEditMode ? 'display: none; ' : ''; ?>background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; border: 1px solid rgba(229, 231, 235, 0.8); padding: 32px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 28px;">
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Client</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['order_customer']); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Inquiry Date</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['order_date']); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Code</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['item_code']); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Name</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['item_name']); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Quantity</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo intval($inquiry['quantity']); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Peso Cost</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;">₱<?php echo number_format(floatval($inquiry['unit_price'] ?? 0), 2); ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Number</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['po_number']) ?: '—'; ?></div>
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Status</label>
                            <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px;"><?php echo h($inquiry['po_status']); ?></div>
                        </div>
                    </div>
                    <div style="margin-bottom: 28px;">
                        <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Notes</label>
                        <div style="padding: 11px 14px; background: #f8f9fa; border-radius: 8px; color: #2c3e50; font-size: 14px; min-height: 60px; line-height: 1.5;"><?php echo nl2br(h($inquiry['notes'])) ?: '—'; ?></div>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="inquiry-details.php?id=<?php echo $inquiryId; ?>&edit=1" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: linear-gradient(135deg, #f4d03f 0%, #f9d76a 100%); border: none; color: #17324d; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; text-decoration: none;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: #e74c3c; border: none; color: #ffffff; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.opacity='0.9';" onmouseout="this.style.opacity='1';" onclick="document.getElementById('deleteConfirmModal').style.display='flex';"> 
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <!-- EDIT MODE -->
                <div id="editMode" style="<?php echo $autoEditMode ? 'display: block;' : 'display: none;'; ?> background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 12px; border: 1px solid rgba(229, 231, 235, 0.8); padding: 32px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);">
                    <form method="post" action="inquiry-details.php?id=<?php echo $inquiryId; ?>" id="editForm">
                        <input type="hidden" name="action" value="update_inquiry">
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; margin-bottom: 24px;">
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Client</label>
                                <input type="text" name="customer" value="<?php echo h($inquiry['order_customer']); ?>" required style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Inquiry Date</label>
                                <input type="date" name="order_date" value="<?php echo h($inquiry['order_date']); ?>" required style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Code</label>
                                <input type="text" name="item_code" value="<?php echo h($inquiry['item_code']); ?>" required style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Product Name</label>
                                <input type="text" name="item_name" value="<?php echo h($inquiry['item_name']); ?>" required style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Quantity</label>
                                <input type="number" name="quantity" value="<?php echo intval($inquiry['quantity']); ?>" min="1" required style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Peso Cost</label>
                                <input type="number" name="unit_price" value="<?php echo floatval($inquiry['unit_price'] ?? 0); ?>" min="0" step="0.01" style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Number</label>
                                <input type="text" name="po_number" value="<?php echo h($inquiry['po_number'] ?? ''); ?>" placeholder="Optional" style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">PO Status</label>
                                <select name="po_status" style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px; cursor: pointer;">
                                    <option value="No PO" <?php echo $inquiry['po_status'] === 'No PO' ? 'selected' : ''; ?>>No PO</option>
                                    <option value="Pending" <?php echo $inquiry['po_status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Received" <?php echo $inquiry['po_status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                                </select>
                            </div>
                        </div>
                        <div style="margin-bottom: 24px;">
                            <label style="display: block; margin-bottom: 10px; color: #7f8c8d; font-weight: 600; font-size: 12px; letter-spacing: 0.3px; text-transform: uppercase;">Notes</label>
                            <textarea name="notes" style="width: 100%; padding: 12px 14px; border: 1px solid #d1d9e6; border-radius: 8px; font-family: inherit; font-size: 14px; min-height: 100px; resize: vertical;"><?php echo h($inquiry['notes'] ?? ''); ?></textarea>
                        </div>
                        <div style="display: flex; justify-content: flex-end; gap: 10px;">
                            <a href="inquiry-details.php?id=<?php echo $inquiryId; ?>" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: #e0e6ed; border: none; color: #2c3e50; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; text-decoration: none;" onmouseover="this.style.background='#d0d8e0';" onmouseout="this.style.background='#e0e6ed';">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; border-radius: 8px; background: linear-gradient(135deg, #f4d03f 0%, #f9d76a 100%); border: none; color: #17324d; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <form id="deleteForm" method="post" action="inquiry-details.php?id=<?php echo $inquiryId; ?>" style="display: none;">
                <input type="hidden" name="action" value="delete_inquiry">
            </form>

            <!-- Delete Confirmation Modal -->
            <div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; justify-content: center; align-items: center;">
                <div style="background: white; border-radius: 16px; padding: 32px; max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideUp 0.3s ease;">
                    <div style="margin-bottom: 24px;">
                        <div style="width: 56px; height: 56px; background: rgba(231, 76, 60, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                            <i class="fas fa-exclamation-triangle" style="color: #e74c3c; font-size: 28px;"></i>
                        </div>
                        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700; color: #2c3e50;">Delete Inquiry</h3>
                        <p style="margin: 0; font-size: 14px; color: #7f8c8d; line-height: 1.5;">Are you sure you want to delete this inquiry? This action cannot be undone.</p>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <button type="button" onclick="document.getElementById('deleteConfirmModal').style.display='none';" style="padding: 12px 16px; border-radius: 8px; border: 1px solid #d1d9e6; background: #f8f9fa; color: #2c3e50; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#e8ecf1';" onmouseout="this.style.background='#f8f9fa';">Cancel</button>
                        <button type="button" onclick="document.getElementById('deleteForm').submit();" style="padding: 12px 16px; border-radius: 8px; border: none; background: #e74c3c; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease;" onmouseover="this.style.background='#c0392b';" onmouseout="this.style.background='#e74c3c';">Yes, Delete</button>
                    </div>
                </div>
            </div>

            <style>
                @keyframes slideUp {
                    from {
                        transform: translateY(20px);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
            </style>
        </main>
    </div>

    <script>
        // Hamburger Menu Toggle
        const hamburgerBtn = document.getElementById('hamburgerBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        if (hamburgerBtn && sidebar) {
            hamburgerBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                if (mainContent) {
                    mainContent.classList.toggle('sidebar-collapsed');
                }
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
            });

            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
                if (mainContent) {
                    mainContent.classList.add('sidebar-collapsed');
                }
            }
        }

        // Profile Dropdown Menu
        const profileBtn = document.getElementById('profileBtn');
        const profileMenu = document.getElementById('profileMenu');
        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
        }

        const profileLinks = document.querySelectorAll('#profileMenu a');
        profileLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (profileMenu) profileMenu.classList.remove('show');
            });
        });
    </script>
</body>
</html>
