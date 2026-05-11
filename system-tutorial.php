<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

// Include sidebar
require_once 'sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Tutorial - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="rel preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tutorial-header {
            background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%);
            padding: 40px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 1px solid #c5ddf0;
        }

        .tutorial-header h1 {
            color: #1a3a5c;
            margin: 0 0 10px 0;
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .tutorial-header p {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }

        .tutorial-nav {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .tutorial-nav button {
            padding: 10px 20px;
            background: #f0f5ff;
            border: 1px solid #c5ddf0;
            color: #1a3a5c;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            font-family: Poppins, sans-serif;
        }

        .tutorial-nav button:hover,
        .tutorial-nav button.active {
            background: #1e88e5;
            border-color: #1e88e5;
            color: #fff;
        }

        .tutorial-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            display: none;
        }

        .tutorial-section.active {
            display: block;
        }

        .tutorial-section h2 {
            color: #1e88e5;
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(30, 136, 229, 0.2);
        }

        .tutorial-section h3 {
            color: #0097a7;
            font-size: 18px;
            margin-top: 25px;
            margin-bottom: 12px;
        }

        .tutorial-content {
            color: #333;
            line-height: 1.8;
            font-size: 14px;
        }

        .tutorial-content p {
            margin: 12px 0;
        }

        .tutorial-content ul, .tutorial-content ol {
            margin: 15px 0;
            padding-left: 25px;
        }

        .tutorial-content li {
            margin: 8px 0;
        }

        .step-box {
            background: rgba(30, 136, 229, 0.1);
            border-left: 4px solid #1e88e5;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .tip-box {
            background: rgba(0, 150, 136, 0.1);
            border-left: 4px solid #00897b;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #fbc02d;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .feature-card {
            background: rgba(30, 136, 229, 0.1);
            border: 1px solid rgba(30, 136, 229, 0.3);
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: #1e88e5;
            box-shadow: 0 5px 15px rgba(30, 136, 229, 0.2);
        }

        .feature-card h4 {
            color: #1e88e5;
            margin-top: 0;
            margin-bottom: 10px;
        }

        .feature-card p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }

        .keyboard-key {
            display: inline-block;
            background: #f0f5ff;
            border: 1px solid #1e88e5;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: #1e88e5;
            font-weight: 600;
            margin: 0 2px;
        }

        /* Light mode adjustments */
        html.light-mode .tutorial-header,
        body.light-mode .tutorial-header {
            background: linear-gradient(135deg, #f8fbff 0%, #e3f2fd 100%);
            border: 1px solid #c5ddf0;
        }

        html.light-mode .tutorial-header h1,
        body.light-mode .tutorial-header h1 {
            color: #1a3a5c;
        }

        html.light-mode .tutorial-header p,
        body.light-mode .tutorial-header p {
            color: #666;
        }

        html.light-mode .tutorial-section,
        body.light-mode .tutorial-section {
            background: #fff;
            border: 1px solid #e0e0e0;
        }

        html.light-mode .tutorial-section h2,
        body.light-mode .tutorial-section h2 {
            color: #1e88e5;
            border-bottom: 2px solid rgba(30, 136, 229, 0.2);
        }

        html.light-mode .tutorial-section h3,
        body.light-mode .tutorial-section h3 {
            color: #0097a7;
        }

        html.light-mode .tutorial-content,
        body.light-mode .tutorial-content {
            color: #333;
        }

        html.light-mode .step-box,
        body.light-mode .step-box {
            background: rgba(30, 136, 229, 0.1);
            border-left-color: #1e88e5;
        }

        html.light-mode .tip-box,
        body.light-mode .tip-box {
            background: rgba(0, 150, 136, 0.1);
            border-left-color: #00897b;
        }

        html.light-mode .warning-box,
        body.light-mode .warning-box {
            background: rgba(255, 193, 7, 0.1);
            border-left-color: #fbc02d;
        }
    </style>
</head>
<body class="light-mode">
    <!-- TOP NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-start">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">
                    <span></span><span></span><span></span>
                </button>
                <div class="logo">
                    <a href="index.php" style="display:flex;align-items:center;">
                        <img src="assets/logo.png" alt="Andison" style="height:48px;width:auto;object-fit:contain;">
                    </a>
                </div>
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

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">
        <div class="tutorial-header">
            <h1><i class="fas fa-book"></i> BW Gas Detector System Tutorial</h1>
            <p>Complete user manual for managing inventory, delivery records, and sales data</p>
            <p style="font-size: 12px; color: #707070; margin-top: 10px;">Last Updated: May 11, 2026</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="tutorial-nav">
            <button class="tutorial-btn active" onclick="showSection('overview')">📋 Overview</button>
            <button class="tutorial-btn" onclick="showSection('dashboard')">📊 Dashboard</button>
            <button class="tutorial-btn" onclick="showSection('sales-overview')">📈 Sales Overview</button>
            <button class="tutorial-btn" onclick="showSection('sales-records')">🗒️ Sales Records</button>
            <button class="tutorial-btn" onclick="showSection('inquiry')">📋 Inquiry</button>
            <button class="tutorial-btn" onclick="showSection('delivery')">🚚 Delivery</button>
            <button class="tutorial-btn" onclick="showSection('inventory')">📦 Inventory</button>
            <button class="tutorial-btn" onclick="showSection('andison')">🚚 Andison Manila</button>
            <button class="tutorial-btn" onclick="showSection('clients')">🏢 Clients</button>
            <button class="tutorial-btn" onclick="showSection('models')">🔧 Models</button>
            <button class="tutorial-btn" onclick="showSection('analytics')">📊 Analytics</button>
            <button class="tutorial-btn" onclick="showSection('reports')">📄 Reports</button>
            <button class="tutorial-btn" onclick="showSection('upload')">⬆️ Upload</button>
            <button class="tutorial-btn" onclick="showSection('warranty')">🛠️ Warranty</button>
            <button class="tutorial-btn" onclick="showSection('access')">🔐 Access</button>
            <button class="tutorial-btn" onclick="showSection('employees')">👥 Employees</button>
            <button class="tutorial-btn" onclick="showSection('settings')">⚙️ Settings</button>
            <button class="tutorial-btn" onclick="showSection('limitations')">⚠️ Limitations</button>
            <button class="tutorial-btn" onclick="showSection('tips')">💡 Tips</button>
        </div>

        <!-- Overview Section -->
        <div id="overview" class="tutorial-section active">
            <h2><i class="fas fa-compass"></i> System Overview</h2>
            <div class="tutorial-content">
                <p>Welcome to the BW Gas Detector Management System! This comprehensive platform helps you manage inventory, track sales, and generate detailed reports for the Andison Industrial gas detector business.</p>

                <h3>Key Features</h3>
                <div class="feature-grid">
                    <div class="feature-card">
                        <h4><i class="fas fa-chart-line"></i> Dashboard</h4>
                        <p>Real-time overview of sales, inventory, and key metrics at a glance.</p>
                    </div>
                    <div class="feature-card">
                        <h4><i class="fas fa-clipboard-list"></i> Sales Records</h4>
                        <p>Track all sales transactions, customer information, and sales performance.</p>
                    </div>
                    <div class="feature-card">
                        <h4><i class="fas fa-boxes"></i> Inventory</h4>
                        <p>Manage product stock levels, item codes, and warehouse locations.</p>
                    </div>
                    <div class="feature-card">
                        <h4><i class="fas fa-truck"></i> Delivery Records</h4>
                        <p>Monitor shipments, delivery dates, and customer recipients.</p>
                    </div>
                    <div class="feature-card">
                        <h4><i class="fas fa-file-alt"></i> Reports</h4>
                        <p>Generate comprehensive reports in PDF, CSV, and Excel formats.</p>
                    </div>
                    <div class="feature-card">
                        <h4><i class="fas fa-users"></i> Client Management</h4>
                        <p>View and manage company profiles and client relationships.</p>
                    </div>
                </div>

                <h3>System Navigation</h3>
                <p>Use the left sidebar to navigate between different sections of the system:</p>
                <ul>
                    <li><strong>Dashboard:</strong> Your main hub for system overview</li>
                    <li><strong>Sales Overview:</strong> Visual charts and sales trends</li>
                    <li><strong>Sales Records:</strong> Detailed transaction records</li>
                    <li><strong>Inquiry:</strong> Browse customer inquiries and orders</li>
                    <li><strong>Delivery Records:</strong> Track all shipments</li>
                    <li><strong>Inventory:</strong> Manage stock and products</li>
                    <li><strong>Analytics:</strong> Deep-dive data analysis</li>
                    <li><strong>Reports:</strong> Export and view reports</li>
                </ul>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> You can change the dataset filter from "ALL DATA" to view specific data ranges using the dropdown at the top of each page.
                </div>
            </div>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="tutorial-section">
            <h2><i class="fas fa-chart-line"></i> Dashboard Guide</h2>
            <div class="tutorial-content">
                <p>The Dashboard is your main hub for monitoring key business metrics and recent activities.</p>

                <h3>Dashboard Sections</h3>

                <div class="step-box">
                    <strong>1. Key Metrics Cards</strong>
                    <p>At the top of the dashboard, you'll see important KPIs:</p>
                    <ul>
                        <li><strong>Total Sales:</strong> Cumulative sales revenue</li>
                        <li><strong>Orders Processed:</strong> Number of customer orders</li>
                        <li><strong>Active Clients:</strong> Count of unique customers</li>
                        <li><strong>Delivery Status:</strong> Current shipment status</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>2. Sales Trend Chart</strong>
                    <p>Visual representation of sales performance over time. Hover over data points to see detailed values.</p>
                </div>

                <div class="step-box">
                    <strong>3. Top Products</strong>
                    <p>List of best-selling items with quantity and revenue data. Click on any product to view detailed analytics.</p>
                </div>

                <div class="step-box">
                    <strong>4. Recent Deliveries</strong>
                    <p>Latest shipments and their current status. Updates in real-time as deliveries are processed.</p>
                </div>

                <div class="tip-box">
                    <strong>💡 Pro Tip:</strong> Use the dataset dropdown to filter dashboard data by specific date ranges or categories.
                </div>
            </div>
        </div>

        <!-- Sales Overview Section -->
        <div id="sales-overview" class="tutorial-section">
            <h2><i class="fas fa-chart-pie"></i> Sales Overview</h2>
            <div class="tutorial-content">
                <p>The Sales Overview page provides comprehensive visual analytics of your sales performance with interactive charts, key metrics, and monthly trends.</p>

                <div class="step-box">
                    <strong>Step 1: Access Sales Overview</strong>
                    <p>Click "Sales Overview" in the sidebar to view sales analytics dashboard.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Filter by Year</strong>
                    <ul>
                        <li>Use the "Filter by Year" dropdown at the top to select the year you want to analyze</li>
                        <li>The default year is the current year (2026)</li>
                        <li>Available years: 2024, 2025, 2026, and earlier</li>
                        <li>Click "Clear Filter" to reset to default</li>
                    </ul>
                </div>

                <h3>Dashboard Sections</h3>

                <div class="step-box">
                    <strong>1. Key Metrics Cards (Top Section)</strong>
                    <p>Two important KPIs displayed at the top:</p>
                    <ul>
                        <li><strong>Units Sold:</strong> Total quantity of units sold to companies</li>
                        <li><strong>Total Deliveries:</strong> Number of delivery records in the selected year</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>2. Monthly Overview Section</strong>
                    <ul>
                        <li><strong>Units Delivered per Month:</strong> Line/bar chart showing delivery quantities by month (January through December)</li>
                        <li>Hover over data points to see exact quantities</li>
                        <li>Use the year dropdown above the chart to view different years</li>
                        <li><strong>Top Products:</strong> List of top 5 best-selling products with names and quantities</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>3. Recent Deliveries Section</strong>
                    <ul>
                        <li>Table showing the 10 most recent delivery records</li>
                        <li><strong>Invoice No:</strong> Unique identifier for the transaction</li>
                        <li><strong>Item Name:</strong> Product name</li>
                        <li><strong>Quantity:</strong> Number of units delivered</li>
                        <li><strong>Company:</strong> Customer company name</li>
                        <li><strong>Delivery Date:</strong> Date of delivery</li>
                        <li>Records are sorted by most recent first</li>
                    </ul>
                </div>

                <h3>Interpreting the Data</h3>

                <div class="step-box">
                    <strong>Understanding Sales Trends</strong>
                    <ul>
                        <li>Monthly chart shows seasonal patterns in sales</li>
                        <li>Peak months indicate high-demand periods</li>
                        <li>Use this data for inventory planning and forecasting</li>
                        <li>Compare year-over-year performance using the year filter</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Top Products Analysis</strong>
                    <ul>
                        <li>Identifies which products are generating the most volume</li>
                        <li>Helps prioritize inventory and marketing efforts</li>
                        <li>Use this information for inventory stocking decisions</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Review Sales Overview regularly (weekly/monthly) to monitor trends and make data-driven decisions about inventory levels and sales strategies.
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Compare current year data with previous years to identify growth patterns and seasonal variations.
                </div>
            </div>
        </div>

        <!-- Delivery Section -->
        <div id="delivery" class="tutorial-section">
            <h2><i class="fas fa-truck"></i> Delivery Records</h2>
            <div class="tutorial-content">
                <p>Track and manage all product shipments and deliveries to customers.</p>

                <div class="step-box">
                    <strong>Step 1: View Delivery Records</strong>
                    <p>Click "Delivery Records" in the sidebar to see all shipments.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Understand Delivery Status</strong>
                    <ul>
                        <li><strong>Pending:</strong> Order ready for shipment</li>
                        <li><strong>In Transit:</strong> Product is being shipped</li>
                        <li><strong>Delivered:</strong> Product received by customer</li>
                        <li><strong>Cancelled:</strong> Order was cancelled</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Update Delivery Status</strong>
                    <ul>
                        <li>Click on a delivery record</li>
                        <li>Update the status in the status dropdown</li>
                        <li>Add delivery date if applicable</li>
                        <li>Save changes</li>
                    </ul>
                </div>

                <h3>Key Information in Delivery Records</h3>
                <ul>
                    <li><strong>Invoice Number:</strong> Unique identifier for the transaction</li>
                    <li><strong>Company Name:</strong> Customer/recipient organization</li>
                    <li><strong>Sold To:</strong> Actual customer receiving the product</li>
                    <li><strong>Item Code:</strong> Product identifier</li>
                    <li><strong>Quantity:</strong> Number of units shipped</li>
                    <li><strong>Delivery Date:</strong> When product was shipped</li>
                    <li><strong>Status:</strong> Current delivery status</li>
                </ul>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Use the "Transferred To" field to track if items were forwarded to another location or customer.
                </div>
            </div>
        </div>

        <!-- Inventory Section -->
        <div id="inventory" class="tutorial-section">
            <h2><i class="fas fa-boxes"></i> Inventory Management</h2>
            <div class="tutorial-content">
                <p>Manage your product inventory, stock levels, and warehouse operations.</p>

                <h3>Inventory Overview</h3>
                <div class="step-box">
                    <strong>Step 1: Access Inventory</strong>
                    <p>Click "Inventory" in the sidebar to view all products and stock levels.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: View Product Details</strong>
                    <ul>
                        <li>Item Code and Name</li>
                        <li>Current Stock Quantity</li>
                        <li>Unit of Measurement (UOM)</li>
                        <li>Warehouse Location</li>
                        <li>Product Status</li>
                    </ul>
                </div>

                <h3>Adding Stock</h3>
                <div class="step-box">
                    <strong>To Add New Stock:</strong>
                    <ol>
                        <li>Click the "Add Item" or "+" button</li>
                        <li>Fill in product details:
                            <ul>
                                <li>Item Code (e.g., MC-AF-K1)</li>
                                <li>Item Description</li>
                                <li>Quantity to add</li>
                                <li>Unit (units, packs, etc.)</li>
                            </ul>
                        </li>
                        <li>Save the record</li>
                        <li>Item is now added to inventory</li>
                    </ol>
                </div>

                <h3>Updating Stock Levels</h3>
                <div class="step-box">
                    <strong>When Items Are Sold or Used:</strong>
                    <ul>
                        <li>Stock levels automatically update when sales are recorded</li>
                        <li>Check inventory regularly to identify low-stock items</li>
                        <li>Reorder before stock reaches critical levels</li>
                    </ul>
                </div>

                <div class="warning-box">
                    <strong>⚠️ Important:</strong> Do NOT manually reduce stock without recording a corresponding sales or transfer transaction.
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Use the search function to quickly find products by item code or description.
                </div>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports" class="tutorial-section">
            <h2><i class="fas fa-file-alt"></i> Reports & Exports</h2>
            <div class="tutorial-content">
                <p>Generate comprehensive reports and export data in various formats.</p>

                <h3>Available Reports</h3>
                <div class="step-box">
                    <strong>Sales Performance Report</strong>
                    <p>Monthly sales trends, revenue breakdown, and growth analysis</p>
                </div>

                <div class="step-box">
                    <strong>Inventory Status Report</strong>
                    <p>Current stock levels, product details, and warehouse status</p>
                </div>

                <div class="step-box">
                    <strong>Delivery Analytics Report</strong>
                    <p>Shipment performance, delivery times, and status breakdown</p>
                </div>

                <div class="step-box">
                    <strong>Client Analytics Report</strong>
                    <p>Customer performance, order frequency, and revenue contribution</p>
                </div>

                <h3>Exporting Data</h3>
                <div class="step-box">
                    <strong>Step 1: Select Report</strong>
                    <p>Choose the report type you want to export from the Reports page.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Choose Format</strong>
                    <ul>
                        <li><strong>PDF:</strong> For printing and sharing</li>
                        <li><strong>CSV:</strong> For data analysis in spreadsheet software</li>
                        <li><strong>XLSX:</strong> Excel format with formatting</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Download</strong>
                    <p>Click the export button and file will download to your computer.</p>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Use the dataset filter before exporting to get reports for specific time periods or categories.
                </div>
            </div>
        </div>

        <!-- Sales Records Section -->
        <div id="sales-records" class="tutorial-section">
            <h2><i class="fas fa-calendar-alt"></i> Sales Records</h2>
            <div class="tutorial-content">
                <p>Comprehensive view and management of individual sales transactions with detailed information about each sale.</p>

                <div class="step-box">
                    <strong>Step 1: Access Sales Records</strong>
                    <p>Click "Sales Records" in the sidebar to view all sales transactions.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Record Information</strong>
                    <ul>
                        <li><strong>Invoice Number:</strong> Unique identifier for the sale</li>
                        <li><strong>Date:</strong> When the sale was recorded</li>
                        <li><strong>Company:</strong> Customer/Company name</li>
                        <li><strong>Item Code:</strong> Product identifier</li>
                        <li><strong>Description:</strong> Product name and details</li>
                        <li><strong>Quantity:</strong> Units sold</li>
                        <li><strong>Unit Price:</strong> Price per unit</li>
                        <li><strong>Total Amount:</strong> Total sale value</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Search & Filter</strong>
                    <ul>
                        <li>Use the search box to find sales by invoice number or customer</li>
                        <li>Filter by date range to view specific periods</li>
                        <li>Sort by clicking column headers</li>
                        <li>View details by clicking on a record</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Export sales records regularly for accounting and audit purposes.
                </div>
            </div>
        </div>

        <!-- Inquiry Section -->
        <div id="inquiry" class="tutorial-section">
            <h2><i class="fas fa-file-invoice"></i> Inquiry Management</h2>
            <div class="tutorial-content">
                <p>Track and manage customer inquiries, quotations, and purchase orders.</p>

                <div class="step-box">
                    <strong>Step 1: View Inquiries</strong>
                    <p>Click "Inquiry" in the sidebar to see all customer inquiries and orders.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Inquiry Workflow</strong>
                    <ul>
                        <li><strong>New Inquiry:</strong> Customer requests information or quotation</li>
                        <li><strong>Quote Provided:</strong> Send pricing and product information</li>
                        <li><strong>Order Received:</strong> Customer confirms purchase</li>
                        <li><strong>Processing:</strong> Order is being prepared</li>
                        <li><strong>Ready to Ship:</strong> Order packaged and ready</li>
                        <li><strong>Shipped:</strong> Order sent to customer</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Manage Inquiry Details</strong>
                    <ul>
                        <li>Click on an inquiry to view full details</li>
                        <li>Update status as the process progresses</li>
                        <li>Add notes for internal reference</li>
                        <li>Link to related orders</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Keep customer communication records in the inquiry notes for better tracking and follow-up.
                </div>
            </div>
        </div>

        <!-- Andison Manila Section -->
        <div id="andison" class="tutorial-section">
            <h2><i class="fas fa-truck-fast"></i> Andison Manila</h2>
            <div class="tutorial-content">
                <p>Track deliveries and logistics through Andison Manila distribution center.</p>

                <div class="step-box">
                    <strong>Step 1: Access Andison Manila</strong>
                    <p>Click "Andison Manila" in the sidebar to view distribution records.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Track Shipments</strong>
                    <ul>
                        <li>View all shipments through Andison Manila warehouse</li>
                        <li>Track delivery status for each shipment</li>
                        <li>Monitor delivery dates and times</li>
                        <li>Identify routing and distribution patterns</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Update Delivery Status</strong>
                    <ul>
                        <li>Mark items as received at warehouse</li>
                        <li>Update status when items are transferred to customers</li>
                        <li>Record any delivery issues or delays</li>
                        <li>Generate delivery reports</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Use the filter by date to monitor recent deliveries and identify any bottlenecks in the distribution process.
                </div>
            </div>
        </div>

        <!-- Client Companies Section -->
        <div id="clients" class="tutorial-section">
            <h2><i class="fas fa-building"></i> Client Companies</h2>
            <div class="tutorial-content">
                <p>Manage and view information about all client companies and their details.</p>

                <div class="step-box">
                    <strong>Step 1: Access Client Companies</strong>
                    <p>Click "Client Companies" in the sidebar to view all registered customers.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: View Company Information</strong>
                    <ul>
                        <li><strong>Company Name:</strong> Official name of the business</li>
                        <li><strong>Contact Person:</strong> Primary point of contact</li>
                        <li><strong>Phone:</strong> Contact phone number</li>
                        <li><strong>Email:</strong> Email address</li>
                        <li><strong>Address:</strong> Business address</li>
                        <li><strong>City/Region:</strong> Location details</li>
                        <li><strong>Total Orders:</strong> Number of purchases made</li>
                        <li><strong>Total Revenue:</strong> Total amount spent</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Add New Company</strong>
                    <ol>
                        <li>Click "Add Company" or "+" button</li>
                        <li>Fill in company details</li>
                        <li>Enter contact information</li>
                        <li>Save the company profile</li>
                    </ol>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Keep company information up-to-date for accurate billing and communication.
                </div>
            </div>
        </div>

        <!-- Models Section -->
        <div id="models" class="tutorial-section">
            <h2><i class="fas fa-cube"></i> Models & Products</h2>
            <div class="tutorial-content">
                <p>Manage product models, specifications, and product catalog.</p>

                <div class="step-box">
                    <strong>Step 1: Access Models</strong>
                    <p>Click "Models" in the sidebar to view all product models.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Product Model Information</strong>
                    <ul>
                        <li><strong>Model Number:</strong> Product identifier</li>
                        <li><strong>Description:</strong> Product name and details</li>
                        <li><strong>Category:</strong> Product category/group</li>
                        <li><strong>Specifications:</strong> Technical details</li>
                        <li><strong>Unit Price:</strong> Standard selling price</li>
                        <li><strong>Stock Quantity:</strong> Available units</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Add New Model</strong>
                    <ol>
                        <li>Click "Add Model" or "+" button</li>
                        <li>Enter model number and description</li>
                        <li>Input specifications and pricing</li>
                        <li>Save the product model</li>
                    </ol>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Maintain accurate product specifications for proper customer communication and order fulfillment.
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div id="analytics" class="tutorial-section">
            <h2><i class="fas fa-chart-bar"></i> Analytics & Insights</h2>
            <div class="tutorial-content">
                <p>In-depth analysis of business metrics, trends, and performance indicators.</p>

                <div class="step-box">
                    <strong>Step 1: Access Analytics</strong>
                    <p>Click "Analytics" in the sidebar to view detailed business analytics.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Key Analytics Available</strong>
                    <ul>
                        <li><strong>Sales Trends:</strong> Historical sales data and growth patterns</li>
                        <li><strong>Product Performance:</strong> Best-selling products and trends</li>
                        <li><strong>Customer Analysis:</strong> Top customers and purchase patterns</li>
                        <li><strong>Delivery Performance:</strong> On-time delivery rates and metrics</li>
                        <li><strong>Inventory Analytics:</strong> Stock movement and turnover rates</li>
                        <li><strong>Revenue Breakdown:</strong> Sales by category, customer, or product</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Interpret Charts</strong>
                    <ul>
                        <li>Hover over chart elements to see detailed values</li>
                        <li>Use filters to focus on specific data ranges</li>
                        <li>Compare different time periods for trend analysis</li>
                        <li>Export analytics data for further analysis</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Review analytics monthly to identify trends and make data-driven business decisions.
                </div>
            </div>
        </div>

        <!-- Upload Data Section -->
        <div id="upload" class="tutorial-section">
            <h2><i class="fas fa-upload"></i> Upload Data</h2>
            <div class="tutorial-content">
                <p>Import bulk data from Excel files to quickly populate system records.</p>

                <div class="step-box">
                    <strong>Step 1: Prepare Your File</strong>
                    <ul>
                        <li>Use Excel (.xlsx or .xls) format</li>
                        <li>Ensure data is organized in columns</li>
                        <li>Include proper headers in the first row</li>
                        <li>Verify data accuracy before uploading</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 2: Upload File</strong>
                    <ol>
                        <li>Click "Upload Data" in the sidebar</li>
                        <li>Click "Choose File" or drag-and-drop your Excel file</li>
                        <li>Select the data type (Inventory, Sales, Orders, etc.)</li>
                        <li>Click "Upload" button</li>
                    </ol>
                </div>

                <div class="step-box">
                    <strong>Step 3: Verify Import</strong>
                    <ul>
                        <li>Review imported data count</li>
                        <li>Check for any error messages</li>
                        <li>Verify records in the respective modules</li>
                        <li>Make any necessary corrections</li>
                    </ul>
                </div>

                <div class="warning-box">
                    <strong>⚠️ Important:</strong> Always backup your data before uploading bulk data. Test with a small sample first if unsure.
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Download a template from the system for the correct format before preparing your Excel file.
                </div>
            </div>
        </div>

        <!-- Warranty Section -->
        <div id="warranty" class="tutorial-section">
            <h2><i class="fas fa-wrench"></i> Warranty Items & Replacements</h2>
            <div class="tutorial-content">
                <p>Track warranty claims and product replacements for customers.</p>

                <div class="step-box">
                    <strong>Step 1: Access Warranty Module</strong>
                    <p>Click "Warranty Items" in the sidebar to manage warranty records.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Record Warranty Information</strong>
                    <ul>
                        <li><strong>Original Sale:</strong> Link to original sales transaction</li>
                        <li><strong>Product Code:</strong> Item being replaced</li>
                        <li><strong>Issue Description:</strong> Problem with the product</li>
                        <li><strong>Warranty Status:</strong> Valid or Expired</li>
                        <li><strong>Replacement:</strong> New item provided</li>
                        <li><strong>Date Processed:</strong> When replacement was made</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Process Warranty Claim</strong>
                    <ol>
                        <li>Verify original purchase and warranty status</li>
                        <li>Document the issue in detail</li>
                        <li>Process replacement if warranty is valid</li>
                        <li>Update inventory for returned and new items</li>
                        <li>Save warranty record for future reference</li>
                    </ol>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Keep warranty records detailed for quality improvement and customer service tracking.
                </div>
            </div>
        </div>

        <!-- Select Access Section -->
        <div id="access" class="tutorial-section">
            <h2><i class="fas fa-user-shield"></i> Select Access & Permissions</h2>
            <div class="tutorial-content">
                <p>Manage user access levels and permissions for system modules.</p>

                <div class="step-box">
                    <strong>Step 1: Access Control Panel</strong>
                    <p>Click "Select Access" in the sidebar (Admin only) to manage permissions.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: User Access Levels</strong>
                    <ul>
                        <li><strong>Admin:</strong> Full access to all modules and settings</li>
                        <li><strong>Employee:</strong> Access to sales, inventory, and reports</li>
                        <li><strong>Restricted:</strong> Limited access to specific modules</li>
                        <li><strong>View Only:</strong> Can view data but cannot edit</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Modify User Permissions</strong>
                    <ul>
                        <li>Select a user from the list</li>
                        <li>Choose their access level</li>
                        <li>Select specific module permissions</li>
                        <li>Save changes</li>
                    </ul>
                </div>

                <div class="warning-box">
                    <strong>⚠️ Important:</strong> Carefully manage access levels. Grant only necessary permissions to each user. Admin access should be limited to authorized personnel only.
                </div>
            </div>
        </div>

        <!-- Manage Employees Section -->
        <div id="employees" class="tutorial-section">
            <h2><i class="fas fa-users"></i> Manage Employees</h2>
            <div class="tutorial-content">
                <p>Create and manage employee accounts and user profiles.</p>

                <div class="step-box">
                    <strong>Step 1: Access Employee Management</strong>
                    <p>Click "Manage Employees" in the sidebar (Admin only) to manage team members.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Employee Information</strong>
                    <ul>
                        <li><strong>Full Name:</strong> Employee's complete name</li>
                        <li><strong>Email:</strong> Work email address</li>
                        <li><strong>Username:</strong> Login username</li>
                        <li><strong>Position:</strong> Job title or role</li>
                        <li><strong>Department:</strong> Team assignment</li>
                        <li><strong>Status:</strong> Active or Inactive</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Add New Employee</strong>
                    <ol>
                        <li>Click "Add Employee" or "+" button</li>
                        <li>Enter employee details</li>
                        <li>Create system login credentials</li>
                        <li>Assign role and access level</li>
                        <li>Save employee profile</li>
                    </ol>
                </div>

                <div class="step-box">
                    <strong>Step 4: Manage Employee Accounts</strong>
                    <ul>
                        <li>Edit employee information as needed</li>
                        <li>Reset passwords for accounts</li>
                        <li>Change access levels and permissions</li>
                        <li>Deactivate accounts for inactive employees</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Keep employee information current and deactivate accounts promptly when employees leave the organization.
                </div>
            </div>
        </div>

        <!-- Settings Section -->
        <div id="settings" class="tutorial-section">
            <h2><i class="fas fa-cog"></i> Settings & Configuration</h2>
            <div class="tutorial-content">
                <p>Configure system preferences and personal account settings.</p>

                <div class="step-box">
                    <strong>Step 1: Access Settings</strong>
                    <p>Click "Settings" in the sidebar to open settings menu.</p>
                </div>

                <div class="step-box">
                    <strong>Step 2: Available Settings</strong>
                    <ul>
                        <li><strong>My Profile:</strong> Update personal information and profile picture</li>
                        <li><strong>Account Settings:</strong> Change password and login settings</li>
                        <li><strong>Preferences:</strong> System display and notification preferences</li>
                        <li><strong>Help:</strong> Access help documentation and support</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>Step 3: Update Profile</strong>
                    <ol>
                        <li>Go to Settings → My Profile</li>
                        <li>Update your information as needed</li>
                        <li>Upload or change profile picture</li>
                        <li>Save changes</li>
                    </ol>
                </div>

                <div class="step-box">
                    <strong>Step 4: Change Password</strong>
                    <ol>
                        <li>Go to Settings → Account</li>
                        <li>Click "Change Password"</li>
                        <li>Enter current password</li>
                        <li>Enter new password (strong password recommended)</li>
                        <li>Confirm new password</li>
                        <li>Save changes</li>
                    </ol>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Change your password regularly and use strong passwords containing uppercase, lowercase, numbers, and special characters.
                </div>
            </div>
        </div>

        <!-- System Limitations Section -->
        <div id="limitations" class="tutorial-section">
            <h2><i class="fas fa-exclamation-triangle"></i> System Limitations & Constraints</h2>
            <div class="tutorial-content">
                <p>Important information about system limitations and known constraints you should be aware of.</p>

                <h3>Data Import Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ File Upload Limits</strong>
                    <ul>
                        <li>Maximum file size: 10 MB per upload</li>
                        <li>Supported formats: Excel (.xlsx, .xls) only</li>
                        <li>Maximum 5,000 rows per file upload</li>
                        <li>Single upload per transaction</li>
                    </ul>
                </div>

                <div class="warning-box">
                    <strong>⚠️ Records That Cannot Be Imported</strong>
                    <p>The following types of records will fail during bulk import and must be handled separately:</p>
                    <ul>
                        <li><strong>Incomplete records:</strong> Missing required fields (invoice number, company name, item code, quantity, etc.)</li>
                        <li><strong>Invalid data:</strong> Malformed dates, incorrect quantity formats, empty critical fields, or invalid product codes</li>
                        <li><strong>Duplicate records:</strong> Exact duplicates of existing records already in the system</li>
                        <li><strong>Special characters:</strong> Company or product names with unsupported special characters that cause validation errors</li>
                        <li><strong>Data validation failures:</strong> Records that don't match system validation rules (e.g., quantity = 0, invalid date ranges)</li>
                        <li><strong>Exceeding row limits:</strong> Files with more than 5,000 rows per upload</li>
                    </ul>
                </div>

                <div class="step-box">
                    <strong>✅ RECOMMENDED SOLUTION: Manual Entry for Records Beyond System Capacity</strong>
                    <p><strong>When records cannot be imported due to data issues or capacity limits:</strong></p>
                    <ol>
                        <li>Review the import error report to identify which records failed and why</li>
                        <li>Do NOT re-upload the same file - this wastes time and resources</li>
                        <li><strong>BETTER APPROACH:</strong> Use the manual entry forms in the respective modules:
                            <ul>
                                <li>📦 <strong>Inventory:</strong> Click "Add Item" to add individual items/stocks</li>
                                <li>📋 <strong>Orders:</strong> Click "Add Order" to enter PO or sales orders</li>
                                <li>🔧 <strong>Warranty:</strong> Click "Add Warranty Record" to record warranty items</li>
                                <li>🚚 <strong>Delivery:</strong> Click "Add Delivery Record" to log deliveries</li>
                            </ul>
                        </li>
                        <li>Manual entry provides immediate validation feedback - you'll know right away if data is acceptable</li>
                        <li>This approach ensures data accuracy and prevents bulk import errors</li>
                    </ol>
                </div>

                <div class="tip-box">
                    <strong>💡 Pro Tip:</strong> <strong>Manual entry is actually MORE EFFICIENT than bulk import when:</strong>
                    <ul>
                        <li>You have 1-20 problem records (faster than fixing file + re-uploading)</li>
                        <li>Data quality issues are inconsistent or complex</li>
                        <li>You need immediate visual feedback and error messages</li>
                        <li>Records exceed the 5,000 row per file limit - split into multiple uploads OR manually enter the excess</li>
                    </ul>
                    <p><strong>Recommended:</strong> For best results, use manual entry for problematic records and keep bulk import for clean, pre-validated data only.</p>
                </div>

                <h3>Data Display Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ Performance Constraints</strong>
                    <ul>
                        <li>Records per page: Limited to 100 entries for better performance</li>
                        <li>Chart data: Most recent 12 months displayed</li>
                        <li>Real-time sync: Data updates every 5-10 seconds</li>
                        <li>Concurrent users: Maximum 50 simultaneous connections</li>
                    </ul>
                </div>

                <h3>Export Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ Export Constraints</strong>
                    <ul>
                        <li>PDF exports: Maximum 1,000 rows</li>
                        <li>Excel exports: Maximum 65,536 rows (Excel limit)</li>
                        <li>Export timeout: 5 minutes maximum processing time</li>
                        <li>File retention: Exported files deleted after 24 hours</li>
                    </ul>
                </div>

                <h3>Search Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ Search Constraints</strong>
                    <ul>
                        <li>Search results: Limited to 500 matches</li>
                        <li>Search field: Text searches only (not numeric)</li>
                        <li>Minimum characters: At least 2 characters for search</li>
                        <li>Search scope: Current dataset only</li>
                    </ul>
                </div>

                <h3>Date Range Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ Historical Data</strong>
                    <ul>
                        <li>Data retention: 5 years of historical data</li>
                        <li>Deleted records: Cannot be recovered after 30 days</li>
                        <li>Archive data: Contact admin for data older than 5 years</li>
                        <li>Real-time data: Only current month data is real-time</li>
                    </ul>
                </div>

                <h3>User & Permission Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ User Constraints</strong>
                    <ul>
                        <li>Maximum users per license: Based on plan</li>
                        <li>Simultaneous logins: One per user account</li>
                        <li>Session timeout: 30 minutes of inactivity</li>
                        <li>Password reset: Available every 24 hours</li>
                    </ul>
                </div>

                <h3>Browser Compatibility</h3>
                <div class="step-box">
                    <strong>Supported Browsers</strong>
                    <ul>
                        <li>✅ Chrome 90+ (recommended)</li>
                        <li>✅ Firefox 88+</li>
                        <li>✅ Edge 90+</li>
                        <li>✅ Safari 14+</li>
                        <li>❌ Internet Explorer (not supported)</li>
                    </ul>
                </div>

                <h3>Device & Network Limitations</h3>
                <div class="warning-box">
                    <strong>⚠️ Technical Requirements</strong>
                    <ul>
                        <li>Internet speed: Minimum 1 Mbps recommended</li>
                        <li>Screen resolution: Minimum 1024x768 pixels</li>
                        <li>JavaScript: Must be enabled in browser</li>
                        <li>Cookies: Required for session management</li>
                        <li>Mobile support: Partial (some features optimized for desktop)</li>
                    </ul>
                </div>

                <h3>Backup & Recovery Limitations</h3>
                <div class="step-box">
                    <strong>Data Backup Information</strong>
                    <ul>
                        <li>Automatic backups: Daily at 2:00 AM (system time)</li>
                        <li>Backup retention: 30 days</li>
                        <li>Recovery time: Up to 24 hours</li>
                        <li>Restore requests: Must be submitted to admin</li>
                    </ul>
                </div>

                <div class="tip-box">
                    <strong>💡 Tip:</strong> Be aware of these limitations when planning data imports, exports, and system usage. For questions or to request extensions, contact your system administrator.
                </div>
            </div>
        </div>

        <!-- Tips & Tricks Section -->
        <div id="tips" class="tutorial-section">
            <h2><i class="fas fa-lightbulb"></i> Tips & Tricks</h2>
            <div class="tutorial-content">
                <h3>Navigation Tips</h3>
                <div class="step-box">
                    <strong>Quick Navigation</strong>
                    <ul>
                        <li>Click the hamburger menu (☰) to collapse/expand the sidebar</li>
                        <li>Use the dataset dropdown to filter data by time period</li>
                        <li>Click your profile name to access account settings</li>
                    </ul>
                </div>

                <h3>Data Management Tips</h3>
                <div class="step-box">
                    <strong>Best Practices</strong>
                    <ul>
                        <li>Always verify customer information before processing sales</li>
                        <li>Keep inventory records up-to-date</li>
                        <li>Update delivery status as soon as shipments are processed</li>
                        <li>Regularly back up important data</li>
                        <li>Use consistent product naming conventions</li>
                    </ul>
                </div>

                <h3>Search & Filter Tips</h3>
                <div class="step-box">
                    <strong>Finding Information Quickly</strong>
                    <ul>
                        <li>Use the search box for quick lookups by invoice number, customer name, or product code</li>
                        <li>Apply date filters to narrow down results</li>
                        <li>Use status filters to find specific order states</li>
                        <li>Sort columns by clicking the column header</li>
                    </ul>
                </div>

                <h3>Keyboard Shortcuts</h3>
                <div class="step-box">
                    <strong>Quick Actions</strong>
                    <ul>
                        <li><span class="keyboard-key">Ctrl</span> + <span class="keyboard-key">P</span> → Print current page</li>
                        <li><span class="keyboard-key">Ctrl</span> + <span class="keyboard-key">F</span> → Search on page</li>
                        <li><span class="keyboard-key">Ctrl</span> + <span class="keyboard-key">S</span> → Save/Export</li>
                        <li><span class="keyboard-key">Escape</span> → Close modals/popups</li>
                    </ul>
                </div>

                <h3>Export Tips</h3>
                <div class="step-box">
                    <strong>Data Export Best Practices</strong>
                    <ul>
                        <li>Filter data before exporting to reduce file size</li>
                        <li>Use PDF for sharing with non-technical users</li>
                        <li>Use Excel for data analysis and calculations</li>
                        <li>Use CSV for importing to other systems</li>
                        <li>Include export date in filenames for record-keeping</li>
                    </ul>
                </div>

                <h3>Troubleshooting</h3>
                <div class="warning-box">
                    <strong>Common Issues & Solutions</strong>
                    <ul>
                        <li><strong>Data not loading:</strong> Try refreshing the page or clearing browser cache</li>
                        <li><strong>Export not working:</strong> Ensure you have a stable internet connection</li>
                        <li><strong>Filters not applying:</strong> Check if dataset is properly selected</li>
                        <li><strong>Need help?:</strong> Contact system administrator or refer to Help section</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            const sections = document.querySelectorAll('.tutorial-section');
            sections.forEach(section => section.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tutorial-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show selected section
            const selectedSection = document.getElementById(sectionId);
            if (selectedSection) {
                selectedSection.classList.add('active');
            }

            // Mark button as active
            event.target.classList.add('active');
        }

        // Sidebar toggle functionality
        document.getElementById('hamburgerBtn').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('sidebar-collapsed');
        });

        // Profile dropdown
        document.getElementById('profileBtn').addEventListener('click', function() {
            document.getElementById('profileMenu').classList.toggle('show');
        });

        document.addEventListener('click', function(event) {
            const profileMenu = document.getElementById('profileMenu');
            const profileBtn = document.getElementById('profileBtn');
            if (!profileBtn.contains(event.target) && !profileMenu.contains(event.target)) {
                profileMenu.classList.remove('show');
            }
        });
    </script>
</body>
</html>
