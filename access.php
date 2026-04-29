<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

// Only admins can access this page
if (($_SESSION['user_role'] ?? 'admin') !== 'admin') {
    http_response_code(403);
    die('Access Denied: Only administrators can access this page.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Access - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .access-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Enhanced page title for access page */
        .page-title {
            background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            filter: drop-shadow(0 2px 4px rgba(91, 188, 255, 0.2));
        }

        .permission-card {
            background: linear-gradient(135deg, #1a2940 0%, #2d4563 50%, #1f3a52 100%);
            border-radius: 14px;
            border: 1px solid rgba(91, 188, 255, 0.15);
            padding: 24px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .permission-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #5bbcff, #2f7eb3);
            opacity: 0;
            transition: opacity 0.35s ease;
        }

        .permission-card:hover {
            border-color: rgba(91, 188, 255, 0.35);
            box-shadow: 0 6px 20px rgba(91, 188, 255, 0.15), 0 2px 8px rgba(0, 0, 0, 0.2);
            transform: translateY(-2px);
        }

        .permission-card:hover::before {
            opacity: 1;
        }

        .permission-info {
            flex: 1;
        }

        .permission-label {
            font-size: 16px;
            font-weight: 700;
            color: #e8f0fa;
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.3px;
        }

        .permission-label i {
            color: #5bbcff;
            font-size: 18px;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(91, 188, 255, 0.1);
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .permission-card:hover .permission-label i {
            background: rgba(91, 188, 255, 0.2);
            transform: scale(1.1);
        }

        .permission-desc {
            font-size: 12px;
            color: #8fa3b8;
            margin-top: 6px;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        .toggle-switch {
            position: relative;
            width: 56px;
            height: 32px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(91, 188, 255, 0.2);
            user-select: none;
            flex-shrink: 0;
            margin-left: 16px;
        }

        .toggle-switch:hover {
            background: rgba(91, 188, 255, 0.08);
            border-color: rgba(91, 188, 255, 0.3);
        }

        .toggle-switch.active {
            background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%);
            border-color: #5bbcff;
            box-shadow: 0 4px 12px rgba(91, 188, 255, 0.25);
        }

        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 26px;
            height: 26px;
            background: white;
            border-radius: 12px;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .toggle-switch.active .toggle-slider {
            left: 27px;
        }

        .status-message {
            margin-top: 24px;
            padding: 16px;
            border-radius: 10px;
            text-align: center;
            display: none;
            font-weight: 600;
            font-size: 14px;
            animation: slideIn 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .status-message.success {
            background: linear-gradient(135deg, rgba(39, 174, 96, 0.15) 0%, rgba(46, 213, 115, 0.1) 100%);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }

        .status-message.error {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15) 0%, rgba(255, 107, 107, 0.1) 100%);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }

        .btn-save-all {
            background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%);
            color: white;
            border: none;
            padding: 13px 32px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            display: block;
            margin: 28px auto 0;
            box-shadow: 0 4px 12px rgba(91, 188, 255, 0.25);
            letter-spacing: 0.5px;
        }

        .btn-save-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(91, 188, 255, 0.35);
        }

        .btn-save-all:active {
            transform: translateY(-1px);
        }

        .btn-save-all:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .btn-save-all.loading {
            position: relative;
            color: transparent;
        }

        .btn-save-all.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Toast Notification Popup */
        .toast-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            min-width: 320px;
            padding: 18px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            font-weight: 600;
            font-size: 15px;
            z-index: 10001;
            animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(400px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(400px);
            }
        }

        .toast-notification.success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            border: 1px solid rgba(46, 213, 115, 0.3);
        }

        .toast-notification.error {
            background: linear-gradient(135deg, #e74c3c 0%, #ff6b6b 100%);
            color: white;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        .toast-notification i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .toast-notification.hide {
            animation: slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        /* Light mode styles */
        html.light-mode .permission-card,
        body.light-mode .permission-card {
            background: linear-gradient(135deg, #f5f9fd 0%, #eef4fa 100%);
            border-color: #c5d9ed;
            box-shadow: 0 2px 8px rgba(45, 69, 99, 0.08);
        }

        html.light-mode .permission-card:hover {
            background: linear-gradient(135deg, #f8fbfe 0%, #f2f7fc 100%);
            border-color: #5bbcff;
            box-shadow: 0 6px 20px rgba(91, 188, 255, 0.12);
        }

        html.light-mode .permission-label {
            color: #1a3a5c;
        }

        html.light-mode .permission-label i {
            background: rgba(91, 188, 255, 0.08);
            color: #2f7eb3;
        }

        html.light-mode .permission-card:hover .permission-label i {
            background: rgba(91, 188, 255, 0.15);
        }

        html.light-mode .permission-desc {
            color: #5a7a99;
        }

        html.light-mode .toggle-switch {
            background: rgba(45, 69, 99, 0.08);
            border-color: #c5d9ed;
        }

        html.light-mode .toggle-switch:hover {
            background: rgba(91, 188, 255, 0.06);
            border-color: #5bbcff;
        }

        html.light-mode .toggle-switch.active {
            background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%);
        }

        /* Light mode - Header and Statistics */
        html.light-mode [style*="background: linear-gradient(135deg, #0d1f3c"] {
            background: linear-gradient(135deg, #f0f6fb 0%, #e8f2f9 50%, #f2f7fc 100%) !important;
            border-color: #c5d9ed !important;
        }

        html.light-mode [style*="color: #ffffff"] {
            color: #1a3a5c !important;
        }

        html.light-mode [style*="color: #a0c4dd"] {
            color: #5a7a99 !important;
        }

        html.light-mode [style*="color: #7a96b0"] {
            color: #6b8dad !important;
        }

        /* Permission Group/Accordion Styles */
        .permission-group {
            margin-bottom: 24px;
        }

        .permission-group-header {
            background: linear-gradient(135deg, #2a3f5f 0%, #1e2a38 100%);
            border-radius: 12px;
            border: 1px solid rgba(91, 188, 255, 0.2);
            padding: 16px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .permission-group-header:hover {
            background: linear-gradient(135deg, #2f4a6f 0%, #243544 100%);
            border-color: rgba(91, 188, 255, 0.4);
            box-shadow: 0 2px 8px rgba(91, 188, 255, 0.1);
        }

        .permission-group-header.open {
            border-color: rgba(91, 188, 255, 0.5);
        }

        .permission-group-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 16px;
            color: #e8f0fa;
        }

        .permission-group-title i {
            font-size: 18px;
            color: #5bbcff;
        }

        .permission-group-counter {
            background: rgba(91, 188, 255, 0.15);
            color: #5bbcff;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
        }

        .permission-group-toggle {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5bbcff;
            transition: transform 0.3s ease;
        }

        .permission-group-header.open .permission-group-toggle {
            transform: rotate(180deg);
        }

        .permission-group-items {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: none;
        }

        .permission-group-items.open {
            display: block;
            max-height: 5000px;
        }

        .permission-group-items .permission-card {
            margin-top: 12px;
            border-radius: 10px;
        }

        .permission-group-items .permission-card:first-child {
            margin-top: 0;
            margin-bottom: 12px;
        }

        /* Light mode group styles */
        html.light-mode .permission-group-header,
        body.light-mode .permission-group-header {
            background: linear-gradient(135deg, #f0f6fb 0%, #e8f2f9 100%);
            border-color: #c5d9ed;
        }

        html.light-mode .permission-group-header:hover,
        body.light-mode .permission-group-header:hover {
            background: linear-gradient(135deg, #f5f9fd 0%, #eef4fa 100%);
            border-color: #5bbcff;
        }

        html.light-mode .permission-group-title,
        body.light-mode .permission-group-title {
            color: #1a3a5c;
        }

        html.light-mode .permission-group-title i,
        body.light-mode .permission-group-title i {
            color: #2f7eb3;
        }

        html.light-mode .permission-group-counter,
        body.light-mode .permission-group-counter {
            background: rgba(91, 188, 255, 0.08);
            color: #2f7eb3;
        }

        html.light-mode .permission-group-toggle,
        body.light-mode .permission-group-toggle {
            color: #2f7eb3;
        }
    </style>
</head>
<body>
    <nav class="navbar" id="navbar">
        <div class="navbar-container">
            <!-- Navbar Start -->
            <div class="navbar-start">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar" onclick="toggleSidebar()">
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

            <!-- Navbar Center -->
            <div class="navbar-center">
                <span style="color: #2c3e50; font-size: 18px; font-weight: 600;">Employee Access Control</span>
            </div>

            <!-- Navbar End -->
            <div class="navbar-end">
                <div class="profile-dropdown">
                    <button type="button" class="profile-btn" id="profileBtn" aria-label="Profile menu" onclick="toggleDropdown(event)">
                        <span class="profile-name" id="companyName">Andison Industrial Sales</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="help.php"><i class="fas fa-question-circle"></i> Help</a>
                        <hr style="margin: 8px 0; border: none; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">
        <div class="page-title">
            <i class="fas fa-shield-alt"></i> Employee Access Control
        </div>

        <div class="access-container">
            <!-- Header Section -->
            <div style="background: linear-gradient(135deg, #0d1f3c 0%, #1a2940 50%, #0f2438 100%); border-radius: 16px; border: 1px solid rgba(91, 188, 255, 0.25); padding: 32px; margin-bottom: 32px; box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);">
                <div style="display: flex; align-items: flex-start; gap: 20px;">
                    <div style="width: 64px; height: 64px; background: linear-gradient(135deg, #5bbcff 0%, #2f7eb3 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 12px rgba(91, 188, 255, 0.25);">
                        <i class="fas fa-user-lock" style="color: white; font-size: 28px;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h2 style="color: #ffffff; margin: 0 0 8px 0; font-size: 28px; font-weight: 700; letter-spacing: 0.3px;">Manage Employee Permissions</h2>
                        <p style="color: #a0c4dd; margin: 0 0 12px 0; font-size: 15px; font-weight: 500; line-height: 1.6;">
                            <i class="fas fa-info-circle" style="margin-right: 8px; color: #5bbcff;"></i>
                            Control which features employees can access. Toggle permissions below and save changes.
                        </p>
                        <p style="color: #7a96b0; margin: 0; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                            <i class="fas fa-lightning-bolt" style="color: #5bbcff;"></i> Changes are applied immediately
                        </p>
                    </div>
                </div>
            </div>

            <!-- Statistics Bar -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 32px;">
                <div style="background: linear-gradient(135deg, #1a2940 0%, #2d4563 100%); border-radius: 12px; border: 1px solid rgba(91, 188, 255, 0.15); padding: 20px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700; color: #5bbcff; margin-bottom: 8px;" id="enabledCount">5</div>
                    <div style="font-size: 13px; color: #8fa3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Enabled</div>
                </div>
                <div style="background: linear-gradient(135deg, #1a2940 0%, #2d4563 100%); border-radius: 12px; border: 1px solid rgba(91, 188, 255, 0.15); padding: 20px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700; color: #a0c4dd; margin-bottom: 8px;" id="disabledCount">0</div>
                    <div style="font-size: 13px; color: #8fa3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Disabled</div>
                </div>
                <div style="background: linear-gradient(135deg, #1a2940 0%, #2d4563 100%); border-radius: 12px; border: 1px solid rgba(91, 188, 255, 0.15); padding: 20px; text-align: center;">
                    <div style="font-size: 32px; font-weight: 700; color: #27ae60; margin-bottom: 8px;" id="totalCount">5</div>
                    <div style="font-size: 13px; color: #8fa3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Total Features</div>
                </div>
            </div>

            <!-- Permissions List -->
            <div id="permissionsContainer">
                <!-- Permissions will be loaded here by JavaScript -->
            </div>

            <!-- Save Button -->
            <div style="display: flex; gap: 16px; justify-content: center; margin-top: 40px;">
                <button class="btn-save-all" id="saveAllBtn" onclick="saveAllPermissions()">
                    <i class="fas fa-save" style="margin-right: 8px;"></i> Save All Changes
                </button>
            </div>
        </div>
    </main>

    <script src="js/app.js" defer></script>
    <script>
        // Toggle sidebar
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
        }

        // Toggle dropdown menu
        function toggleDropdown(event) {
            event.preventDefault();
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (dropdownMenu) {
                dropdownMenu.classList.toggle('active');
            }
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const profileBtn = document.getElementById('profileBtn');
            const dropdownMenu = document.getElementById('dropdownMenu');
            if (dropdownMenu && event.target !== profileBtn && !profileBtn.contains(event.target)) {
                dropdownMenu.classList.remove('active');
            }
        });

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            toast.innerHTML = `<i class="${icon}"></i> <span>${message}</span>`;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('hide');
                setTimeout(() => {
                    toast.remove();
                }, 400);
            }, 3000);
        }

        async function loadPermissions() {
            try {
                console.log('Loading permissions from server...');
                const response = await fetch('api/get-permissions.php');
                const data = await response.json();

                console.log('Permissions received:', data);

                if (data.success) {
                    const container = document.getElementById('permissionsContainer');
                    container.innerHTML = '';

                    // Group permissions by category
                    const groups = {
                        'Inquiry': { icon: 'fas fa-envelope', permissions: [] },
                        'Delivery Records': { icon: 'fas fa-truck', permissions: [] },
                        'Inventory': { icon: 'fas fa-boxes', permissions: [] },
                        'Purchase Orders': { icon: 'fas fa-shopping-cart', permissions: [] },
                        'Andison Manila': { icon: 'fas fa-building', permissions: [] },
                        'Warranty Items': { icon: 'fas fa-wrench', permissions: [] }
                    };

                    let enabledCount = 0;
                    let disabledCount = 0;

                    data.permissions.forEach(perm => {
                        const isEnabled = parseInt(perm.enabled) === 1;
                        
                        if (isEnabled) {
                            enabledCount++;
                        } else {
                            disabledCount++;
                        }

                        // Categorize permission
                        if (perm.permission_name.startsWith('inquiry_')) {
                            groups['Inquiry'].permissions.push(perm);
                        } else if (perm.permission_name.startsWith('delivery_')) {
                            groups['Delivery Records'].permissions.push(perm);
                        } else if (perm.permission_name.startsWith('inventory_') && !perm.permission_name.includes('create_po')) {
                            groups['Inventory'].permissions.push(perm);
                        } else if (perm.permission_name.includes('create_po')) {
                            groups['Purchase Orders'].permissions.push(perm);
                        } else if (perm.permission_name.startsWith('andison_')) {
                            groups['Andison Manila'].permissions.push(perm);
                        } else if (perm.permission_name.startsWith('warranty_')) {
                            groups['Warranty Items'].permissions.push(perm);
                        }
                    });

                    // Render groups
                    Object.entries(groups).forEach(([groupName, groupData]) => {
                        if (groupData.permissions.length === 0) return;

                        const groupDiv = document.createElement('div');
                        groupDiv.className = 'permission-group';

                        // Group header
                        const header = document.createElement('div');
                        header.className = 'permission-group-header open';
                        header.onclick = () => toggleGroup(header);
                        header.innerHTML = `
                            <div class="permission-group-title">
                                <i class="${groupData.icon}"></i>
                                ${groupName}
                                <span class="permission-group-counter">${groupData.permissions.length}</span>
                            </div>
                            <div class="permission-group-toggle">
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        `;

                        // Group items container
                        const itemsContainer = document.createElement('div');
                        itemsContainer.className = 'permission-group-items open';

                        groupData.permissions.forEach(perm => {
                            const isEnabled = parseInt(perm.enabled) === 1;
                            const card = document.createElement('div');
                            card.className = 'permission-card';
                            card.innerHTML = `
                                <div class="permission-info">
                                    <div class="permission-label">
                                        <i class="fas fa-check-circle"></i>
                                        ${perm.permission_label}
                                    </div>
                                    <div class="permission-desc">
                                        Permission ID: ${perm.permission_name}
                                    </div>
                                </div>
                                <div class="toggle-switch ${isEnabled ? 'active' : ''}" data-permission="${perm.permission_name}" onclick="togglePermission(this)">
                                    <div class="toggle-slider"></div>
                                </div>
                            `;
                            itemsContainer.appendChild(card);
                        });

                        groupDiv.appendChild(header);
                        groupDiv.appendChild(itemsContainer);
                        container.appendChild(groupDiv);
                    });

                    // Update statistics
                    document.getElementById('enabledCount').textContent = enabledCount;
                    document.getElementById('disabledCount').textContent = disabledCount;
                    document.getElementById('totalCount').textContent = data.permissions.length;
                } else {
                    console.error('Failed to load permissions:', data.message);
                }
            } catch (error) {
                console.error('Error loading permissions:', error);
            }
        }

        function toggleGroup(header) {
            header.classList.toggle('open');
            const itemsContainer = header.nextElementSibling;
            itemsContainer.classList.toggle('open');
        }

        function togglePermission(element) {
            element.classList.toggle('active');
            updateStatistics();
        }

        function updateStatistics() {
            const toggles = document.querySelectorAll('.toggle-switch');
            let enabledCount = 0;
            let disabledCount = 0;

            toggles.forEach(toggle => {
                if (toggle.classList.contains('active')) {
                    enabledCount++;
                } else {
                    disabledCount++;
                }
            });

            document.getElementById('enabledCount').textContent = enabledCount;
            document.getElementById('disabledCount').textContent = disabledCount;
            document.getElementById('totalCount').textContent = toggles.length;
        }

        async function saveAllPermissions() {
            try {
                console.log('=== SAVE PERMISSIONS START ===');
                const btn = document.getElementById('saveAllBtn');
                btn.disabled = true;
                btn.classList.add('loading');

                const toggles = document.querySelectorAll('.toggle-switch');
                const permissions = [];

                toggles.forEach(toggle => {
                    const isActive = toggle.classList.contains('active');
                    const permName = toggle.dataset.permission;
                    const perm = {
                        permission_name: permName,
                        enabled: isActive ? 1 : 0
                    };
                    permissions.push(perm);
                    console.log(`Saving: ${permName} = ${perm.enabled} (active=${isActive})`);
                });

                const payload = { permissions: permissions };
                console.log('Full payload:', JSON.stringify(payload));

                const response = await fetch('api/save-permissions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                btn.disabled = false;
                btn.classList.remove('loading');

                if (data.success) {
                    showToast('✓ Permissions saved successfully!', 'success');
                    // Reload permissions to reflect saved changes
                    console.log('Reloading permissions after successful save...');
                    setTimeout(() => {
                        loadPermissions();
                    }, 500);
                } else {
                    showToast('✗ Failed to save permissions: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Error saving permissions:', error);
                const btn = document.getElementById('saveAllBtn');
                btn.disabled = false;
                btn.classList.remove('loading');
                showToast('✗ An error occurred while saving permissions', 'error');
            }
        }

        // Load permissions on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPermissions();
        });
    </script>
</body>
</html>
