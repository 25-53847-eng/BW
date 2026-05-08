<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

// Ensure user_role is set
if (empty($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'admin';
}

// Only admins can manage employees
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: index.php', true, 302);
    exit;
}

require_once 'db_config.php';

// Get all employees
$employees = [];
$result = $conn->query("SELECT id, name, email, role, created_at FROM users WHERE role = 'employee' ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Employees - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Navbar & Layout Styles */
        body {
            padding-top: 0 !important;
        }

        .navbar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 2147483647 !important;
        }

        .main-content {
            margin-top: 120px !important;
            z-index: 1 !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title-section h1 i {
            color: #5bbcff;
            font-size: 28px;
        }

        .page-title-section p {
            font-size: 13px;
            color: #a0a0a0;
            margin: 5px 0 0 47px;
        }

        .btn-add-employee {
            background: linear-gradient(135deg, #5bbcff, #2f5fa7);
            color: white;
            border: none;
            padding: 11px 22px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-add-employee:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(47, 95, 167, 0.3);
        }

        .employees-table {
            width: 100%;
            background: linear-gradient(135deg, #1a2332 0%, #243447 100%);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .table-header {
            display: grid;
            grid-template-columns: 1.2fr 1.5fr 0.8fr 0.6fr;
            gap: 20px;
            padding: 18px 20px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2) 0%, rgba(47, 95, 167, 0.05) 100%);
            border-bottom: 1px solid rgba(91, 188, 255, 0.15);
            font-weight: 600;
            font-size: 12px;
            color: #7a9dc5;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .table-row {
            display: grid;
            grid-template-columns: 1.2fr 1.5fr 0.8fr 0.6fr;
            gap: 20px;
            padding: 18px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            align-items: center;
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background: linear-gradient(135deg, rgba(91, 188, 255, 0.08) 0%, rgba(47, 95, 167, 0.06) 100%);
            border-bottom-color: rgba(91, 188, 255, 0.2);
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .employee-name {
            color: #e3f2fd;
            font-weight: 600;
            font-size: 14px;
        }

        .employee-email {
            color: #90caf9;
            font-size: 13px;
        }

        .role-badge {
            display: inline-block;
            background: linear-gradient(135deg, rgba(91, 188, 255, 0.2), rgba(47, 95, 167, 0.15));
            color: #5bbcff;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(91, 188, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .created-date {
            color: #90caf9;
            font-size: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-action {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: linear-gradient(135deg, rgba(91, 188, 255, 0.2), rgba(47, 95, 167, 0.15));
            color: #5bbcff;
            border: 1px solid rgba(91, 188, 255, 0.4);
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, rgba(91, 188, 255, 0.3), rgba(47, 95, 167, 0.2));
            border-color: #5bbcff;
        }

        .btn-delete {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.2), rgba(244, 67, 54, 0.15));
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.4);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.3), rgba(244, 67, 54, 0.2));
            border-color: #ff6b6b;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7a9dc5;
        }

        .empty-state-icon {
            font-size: 60px;
            color: #556b82;
            margin-bottom: 20px;
        }

        .empty-state-text {
            font-size: 15px;
            margin-bottom: 30px;
            color: #a0b8d4;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, #1a2332 0%, #243447 100%);
            border: 1px solid rgba(91, 188, 255, 0.2);
            border-radius: 12px;
            padding: 30px;
            max-width: 450px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        .modal-header {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-close {
            background: none;
            border: none;
            color: #90caf9;
            font-size: 24px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #fff;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #90caf9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input {
            width: 100%;
            padding: 11px 14px;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.2), rgba(47, 95, 167, 0.05));
            border: 1px solid rgba(91, 188, 255, 0.2);
            border-radius: 8px;
            color: #e3f2fd;
            font-family: Verdana, sans-serif;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #5bbcff;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.3), rgba(47, 95, 167, 0.1));
            box-shadow: 0 0 10px rgba(91, 188, 255, 0.2);
        }

        .form-input::placeholder {
            color: #7a9dc5;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .btn-submit {
            flex: 1;
            padding: 11px;
            background: linear-gradient(135deg, #5bbcff, #2f5fa7);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(47, 95, 167, 0.3);
        }

        .btn-cancel {
            flex: 1;
            padding: 11px;
            background: rgba(255, 255, 255, 0.08);
            color: #90caf9;
            border: 1px solid rgba(91, 188, 255, 0.3);
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: #5bbcff;
        }

        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 14px 22px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.9), rgba(56, 142, 60, 0.9));
            color: white;
            border-radius: 8px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            display: none;
            animation: slideIn 0.3s ease;
            font-size: 13px;
            font-weight: 500;
        }

        .notification.show {
            display: block;
        }

        .notification.error {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.9), rgba(211, 47, 47, 0.9));
        }

        /* Delete Confirmation Modal */
        .delete-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1500;
            align-items: center;
            justify-content: center;
        }

        .delete-modal.active {
            display: flex;
        }

        .delete-modal-content {
            background: linear-gradient(135deg, #f5f9ff 0%, #eef4fa 100%);
            border: 1px solid rgba(91, 188, 255, 0.3);
            border-radius: 12px;
            padding: 0;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            animation: scaleIn 0.2s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .delete-modal-header {
            padding: 24px 24px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .delete-modal-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.2), rgba(244, 67, 54, 0.15));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff6b6b;
            font-size: 20px;
            flex-shrink: 0;
        }

        .delete-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #1a2a3a;
            margin: 0;
        }

        .delete-modal-body {
            padding: 0 24px 24px;
        }

        .delete-modal-message {
            color: #2a3a4a;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .delete-modal-employee-name {
            color: #ff6b6b;
            font-weight: 600;
        }

        .delete-modal-warning {
            padding: 12px 14px;
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.15), rgba(255, 152, 0, 0.1));
            border-left: 3px solid #ffc107;
            border-radius: 4px;
            font-size: 12px;
            color: #d97706;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }

        .delete-modal-footer {
            padding: 16px 24px 24px;
            display: flex;
            gap: 12px;
        }

        .delete-btn-cancel {
            flex: 1;
            padding: 11px;
            background: rgba(91, 188, 255, 0.15);
            color: #2f5fa7;
            border: 1px solid rgba(91, 188, 255, 0.4);
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .delete-btn-cancel:hover {
            background: rgba(91, 188, 255, 0.25);
            border-color: #2f5fa7;
        }

        .delete-btn-confirm {
            flex: 1;
            padding: 11px;
            background: linear-gradient(135deg, #ff6b6b, #f44336);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .delete-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        body.light-mode .employees-table {
            background: linear-gradient(135deg, #f5f9ff 0%, #eef4fa 100%);
        }

        body.light-mode .table-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.6) 0%, rgba(91, 188, 255, 0.1) 100%);
            border-bottom-color: rgba(91, 188, 255, 0.3);
        }

        body.light-mode .table-row {
            border-bottom-color: rgba(0, 0, 0, 0.05);
        }

        body.light-mode .table-row:hover {
            background: linear-gradient(135deg, rgba(91, 188, 255, 0.1) 0%, rgba(47, 95, 167, 0.08) 100%);
        }

        body.light-mode .employee-name {
            color: #1a2a3a;
        }

        body.light-mode .employee-email {
            color: #3a5a7a;
        }

        /* Password Toggle Button */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-wrapper .form-input {
            flex: 1;
            padding-right: 40px;
        }

        .toggle-password-btn {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #7a9dc5;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .toggle-password-btn:hover {
            background: rgba(91, 188, 255, 0.1);
            color: #5bbcff;
        }

        .toggle-password-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <!-- TOP NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Hamburger Toggle & Logo -->
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

            <!-- Center Title -->
            <div class="navbar-center">
                <h1 class="dashboard-title">Manage Employees</h1>
            </div>

            <!-- Right Profile Section -->
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

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-area">
            <!-- Page Header -->
            <div class="page-header">
                <div class="page-title-section">
                    <h1><i class="fas fa-users" style="color: #5bbcff; margin-right: 12px;"></i>Manage Employees</h1>
                    <p>Create and manage employee accounts and permissions</p>
                </div>
                <button class="btn-add-employee" onclick="openAddEmployeeModal()">
                    <i class="fas fa-plus"></i>
                    Add Employee
                </button>
            </div>

            <!-- Employees Table -->
            <?php if (count($employees) > 0): ?>
                <div class="employees-table">
                    <div class="table-header">
                        <div>Name</div>
                        <div>Email</div>
                        <div>Role</div>
                        <div>Actions</div>
                    </div>
                    <?php foreach ($employees as $emp): ?>
                        <div class="table-row">
                            <div class="employee-name"><?php echo htmlspecialchars($emp['name']); ?></div>
                            <div class="employee-email"><?php echo htmlspecialchars($emp['email']); ?></div>
                            <div><span class="role-badge"><?php echo htmlspecialchars($emp['role']); ?></span></div>
                            <div class="action-buttons">
                                <button class="btn-action btn-edit" onclick="editEmployee(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['name']); ?>', '<?php echo htmlspecialchars($emp['email']); ?>')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-action btn-delete" onclick="deleteEmployee(<?php echo $emp['id']; ?>, '<?php echo htmlspecialchars($emp['name']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="employees-table">
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div class="empty-state-text">
                            No employees yet. Create your first employee account to get started.
                        </div>
                        <button class="btn-add-employee" onclick="openAddEmployeeModal()">
                            <i class="fas fa-plus"></i>
                            Add First Employee
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div id="employeeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span id="modalTitle">Add New Employee</span>
                <button class="modal-close" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <form id="employeeForm" onsubmit="submitEmployeeForm(event)">
                <input type="hidden" id="employeeId" value="">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" id="employeeName" class="form-input" placeholder="Enter employee name" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" id="employeeEmail" class="form-input" placeholder="employee@example.com" required>
                </div>

                <div class="form-group" id="passwordGroup">
                    <label class="form-label">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="employeePassword" class="form-input" placeholder="Enter a strong password" required>
                        <button type="button" class="toggle-password-btn" id="togglePasswordBtn" onclick="togglePasswordVisibility()" tabindex="-1" aria-label="Show/hide password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small style="color: #7a9dc5; margin-top: 6px; display: block; font-size: 11px;">
                        Min 8 characters with uppercase, lowercase, numbers, and symbols
                    </small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <span id="submitBtnText">Create Employee</span>
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeEmployeeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="delete-modal">
        <div class="delete-modal-content">
            <div class="delete-modal-header">
                <div class="delete-modal-icon">
                    <i class="fas fa-exclamation"></i>
                </div>
                <h2 class="delete-modal-title">Delete Employee</h2>
            </div>
            <div class="delete-modal-body">
                <p class="delete-modal-message">
                    Are you sure you want to delete the employee account for <span class="delete-modal-employee-name" id="deleteEmployeeName"></span>?
                </p>
                <div class="delete-modal-warning">
                    <i class="fas fa-info-circle" style="flex-shrink: 0; margin-top: 2px;"></i>
                    <span>This action cannot be undone.</span>
                </div>
            </div>
            <div class="delete-modal-footer">
                <button class="delete-btn-cancel" onclick="closeDeleteConfirmation()">Cancel</button>
                <button class="delete-btn-confirm" onclick="confirmDeleteEmployee()">Delete</button>
            </div>
            <input type="hidden" id="deleteEmployeeId">
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script src="js/app.js"></script>
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('employeePassword');
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const icon = toggleBtn.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                toggleBtn.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggleBtn.setAttribute('aria-label', 'Show password');
            }
        }

        function openAddEmployeeModal() {
            document.getElementById('employeeId').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Employee';
            document.getElementById('employeeForm').reset();
            document.getElementById('employeeName').value = '';
            document.getElementById('employeeEmail').value = '';
            document.getElementById('employeePassword').value = '';
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('submitBtnText').textContent = 'Create Employee';
            document.getElementById('employeeModal').classList.add('active');
        }

        function editEmployee(id, name, email) {
            document.getElementById('employeeId').value = id;
            document.getElementById('modalTitle').textContent = 'Edit Employee';
            document.getElementById('employeeName').value = name;
            document.getElementById('employeeEmail').value = email;
            document.getElementById('employeePassword').value = '';
            document.getElementById('passwordGroup').innerHTML = `
                <label class="form-label">New Password (Leave blank to keep current)</label>
                <div class="password-wrapper">
                    <input type="password" id="employeePassword" class="form-input" placeholder="Optional - leave blank to keep current password">
                    <button type="button" class="toggle-password-btn" id="togglePasswordBtn" onclick="togglePasswordVisibility()" tabindex="-1" aria-label="Show/hide password">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;
            document.getElementById('submitBtnText').textContent = 'Update Employee';
            document.getElementById('employeeModal').classList.add('active');
        }

        function closeEmployeeModal() {
            document.getElementById('employeeModal').classList.remove('active');
        }

        async function submitEmployeeForm(e) {
            e.preventDefault();

            const id = document.getElementById('employeeId').value;
            const name = document.getElementById('employeeName').value.trim();
            const email = document.getElementById('employeeEmail').value.trim();
            const password = document.getElementById('employeePassword').value;

            if (!name || !email) {
                showNotification('Please fill in all required fields', 'error');
                return;
            }

            if (!id && !password) {
                showNotification('Password is required for new employees', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('action', id ? 'update' : 'create');
            formData.append('id', id);
            formData.append('name', name);
            formData.append('email', email);
            if (password) formData.append('password', password);

            try {
                const response = await fetch('api/manage-employees.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(result.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            }
        }

        function openDeleteConfirmation(id, name) {
            document.getElementById('deleteEmployeeId').value = id;
            document.getElementById('deleteEmployeeName').textContent = name;
            document.getElementById('deleteConfirmModal').classList.add('active');
        }

        function closeDeleteConfirmation() {
            document.getElementById('deleteConfirmModal').classList.remove('active');
        }

        async function confirmDeleteEmployee() {
            const id = document.getElementById('deleteEmployeeId').value;
            closeDeleteConfirmation();

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            try {
                const response = await fetch('api/manage-employees.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message);
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(result.message || 'An error occurred', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            }
        }

        async function deleteEmployee(id, name) {
            openDeleteConfirmation(id, name);
        }

        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification show ' + (type === 'error' ? 'error' : '');
            
            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }

        // Close modal when clicking outside
        document.getElementById('employeeModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEmployeeModal();
            }
        });

        document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteConfirmation();
            }
        });
    </script>
</body>
</html>
