<?php
require_once 'includes/auth.php';
requireLogin();
require 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders | ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f8fafc;
            --panel-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --danger: #ef4444;
            --success: #10b981;
            --border: #e2e8f0;
        }
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { margin: 0; background: var(--bg-color); color: var(--text-main); padding: 20px; min-height: 100vh; }
        h1, h2, h3 { margin-top: 0; color: var(--text-main); }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        /* Tabs */
        .tabs { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 1px solid var(--border); padding-bottom: 10px; overflow-x: auto; }
        .tab-btn { background: transparent; border: none; color: var(--text-muted); padding: 12px 24px; cursor: pointer; font-size: 15px; font-weight: 600; border-radius: 8px; transition: all 0.3s ease; white-space: nowrap; }
        .tab-btn:hover { color: var(--text-main); background: #f1f5f9; }
        .tab-btn.active { color: #fff; background: var(--accent); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        
        .tab-content { display: none; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Panels */
        .panel { background: var(--panel-bg); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        
        /* Forms */
        .form-row { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
        label { display: block; margin-bottom: 8px; font-size: 13px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        input, select { width: 100%; padding: 12px; background: #ffffff; border: 1px solid #cbd5e1; color: var(--text-main); border-radius: 8px; font-size: 14px; transition: all 0.3s; }
        input:focus, select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        button { padding: 12px 24px; background: var(--accent); color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        button:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-danger { background: var(--danger); } .btn-danger:hover { background: #dc2626; }
        .btn-success { background: var(--success); } .btn-success:hover { background: #059669; }
        .btn-outline { background: #ffffff; border: 1px solid #cbd5e1; color: var(--text-main); } .btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }

        /* Tables */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 14px; background: #ffffff; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; background: #f8fafc; }
        tr:hover td { background: #f1f5f9; }

        /* Dynamic Rows */
        .po-item-row { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 12px; padding: 16px; background: #f8fafc; border-radius: 8px; border: 1px solid var(--border); }
        .po-item-row .form-group { margin-bottom: 0; }
        .remove-btn { padding: 12px; background: #fee2e2; color: var(--danger); border: 1px solid transparent; }
        .remove-btn:hover { background: #fca5a5; border-color: var(--danger); color: #7f1d1d; }

        .checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; margin-top: 8px; font-size: 14px; font-weight: normal; text-transform: none; color: var(--text-main); }
        .checkbox-label input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-urgent { background: #fee2e2; color: var(--danger); border: 1px solid #fca5a5; }

        .client-group { margin-bottom: 30px; }
        .client-group h3 { color: var(--accent); margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border); }

        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .tabs, .header, .no-print, button { display: none !important; }
            .tab-content { display: block !important; }
            .panel { background: none; border: none; box-shadow: none; padding: 0; margin-bottom: 40px; }
            table { border: 1px solid #ddd; }
            th, td { border: 1px solid #ddd; padding: 8px; color: #000; }
            th { background: #f3f4f6; color: #000; }
            h1, h2, h3 { color: #000; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Purchase Orders</h1>
                <p style="color: var(--text-muted); margin-top: 4px;">Manage purchase orders and view pending materials</p>
            </div>
            <div style="display: flex; gap: 12px; align-items: center;">
                <span style="color: var(--text-muted); font-size: 14px; margin-right: 8px;">Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="clients.php" class="btn-outline" style="text-decoration: none; padding: 10px 20px; border-radius: 8px;">Clients</a>
                <a href="products.php" class="btn-outline" style="text-decoration: none; padding: 10px 20px; border-radius: 8px;">Products</a>
                <?php if (hasRole('Super Admin')): ?>
                    <a href="admin_users.php" class="btn-outline" style="text-decoration: none; padding: 10px 20px; border-radius: 8px;">Manage Users</a>
                <?php endif; ?>
                <a href="logout.php" class="btn-outline" style="text-decoration: none; padding: 10px 20px; border-radius: 8px; border-color: var(--danger); color: var(--danger);">Logout</a>
            </div>
        </div>

        <div class="tabs no-print">
            <button class="tab-btn active" onclick="switchTab('create')">1. Create PO</button>
            <button class="tab-btn" onclick="switchTab('visibility')">2. Orders Visibility</button>
            <button class="tab-btn" onclick="switchTab('pending-fg')">3. Pending Orders (FG)</button>
            <button class="tab-btn" onclick="switchTab('pending-rm')">4. Raw Material Pending</button>
            <?php if ($_SESSION['role_name'] !== 'Sales Executive'): ?>
                <button class="tab-btn" onclick="switchTab('reports')">5. Reports</button>
            <?php endif; ?>
        </div>

        <!-- Section 1: Create PO -->
        <div id="tab-create" class="tab-content active">
            <div class="panel">
                <h2>New Purchase Order</h2>
                <form id="po-form">
                    <input type="hidden" id="po-id" value="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Client Name</label>
                            <select id="po-client" required>
                                <option value="">Select Client...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Order Date</label>
                            <input type="date" id="po-date" required>
                        </div>
                        <div class="form-group">
                            <label>Deadline Date</label>
                            <input type="date" id="po-deadline">
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin: 24px 0 16px;">
                        <h3>Order Items</h3>
                        <div>
                            <button type="button" class="btn-outline" onclick="selectAllUrgent()" style="margin-right: 8px;">Mark All Urgent</button>
                            <button type="button" onclick="addItemRow()">+ Add Item</button>
                        </div>
                    </div>
                    
                    <div id="po-items-container">
                        <!-- Dynamic rows will be added here -->
                    </div>

                    <div style="margin-top: 32px; border-top: 1px solid var(--border); padding-top: 24px; display: flex; justify-content: flex-end;">
                        <button type="submit" class="btn-success">Save Purchase Order & Auto-allocate</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: Orders Visibility -->
        <div id="tab-visibility" class="tab-content">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2>Orders Visibility</h2>
                    <div>
                        <button class="no-print btn-outline" onclick="exportTableToCSV('orders-visibility-container', 'orders_visibility.csv')">Export Excel</button>
                    </div>
                </div>
                <div id="orders-visibility-container">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>

        <!-- Section 3: Pending Orders (Finished Goods) -->
        <div id="tab-pending-fg" class="tab-content">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2>Pending Order (Total for all parties)</h2>
                    <div>
                        <button class="no-print btn-outline" onclick="exportTableToCSV('table-total-pending-fg', 'total_pending_fg.csv')">Export Excel</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="table-total-pending-fg">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Amount</th>
                                <th>Lazer Print</th>
                                <th>Lazer Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2>Pending Order (Client Wise)</h2>
                    <div>
                        <button class="no-print btn-outline" onclick="exportTableToCSV('client-pending-fg-container', 'client_wise_pending_fg.csv')">Export Excel</button>
                    </div>
                </div>
                <div id="client-pending-fg-container">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>

        <!-- Section 4: Raw Material Pending -->
        <div id="tab-pending-rm" class="tab-content">
            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2>Total Raw Material Pending (Club)</h2>
                    <div>
                        <button class="no-print btn-outline" onclick="exportTableToCSV('table-total-pending-rm', 'total_pending_rm.csv')">Export Excel</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="table-total-pending-rm">
                        <thead>
                            <tr>
                                <th>F.G Name</th>
                                <th>Component (Mould/Brass)</th>
                                <th>Process</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h2>Raw Material Pending (Client Wise)</h2>
                    <div>
                        <button class="no-print btn-outline" onclick="exportTableToCSV('client-pending-rm-container', 'client_wise_pending_rm.csv')">Export Excel</button>
                    </div>
                </div>
                <div id="client-pending-rm-container">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>

        <!-- Section 5: Reports / WhatsApp Automation -->
        <div id="tab-reports" class="tab-content">
            <div class="panel" style="text-align: center; padding: 40px 24px;">
                <h2>WhatsApp Automation Linking</h2>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Scan this QR code with your WhatsApp to link and automate daily reports.</p>
                
                <div style="background: #f8fafc; border: 1px solid var(--border); padding: 24px; display: inline-block; border-radius: 12px; min-height: 250px; min-width: 250px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                    <img id="wa-qr-img" src="" alt="WhatsApp QR Code" style="width: 200px; height: 200px; display: none;">
                </div>
                
                <div style="margin-top: 24px;">
                    <p id="wa-status-text" style="color: var(--text-muted); font-weight: 600;">Status: Checking connection...</p>
                    <button id="wa-logout-btn" class="btn-danger" style="display: none; margin-top: 16px; margin-left: auto; margin-right: auto;" onclick="logoutWhatsApp()">Logout WhatsApp</button>
                </div>
            </div>
        </div>

    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx/dist/xlsx.full.min.js"></script>
    <script>
        const userRole = <?= json_encode($_SESSION['role_name'] ?? '') ?>;
        
        let clientsList = [];
        let productsList = [];
        let qrInterval = null;

        // Tab Switching
        function switchTab(tabId) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            const btn = document.querySelector(`.tab-btn[onclick="switchTab('${tabId}')"]`);
            if (btn) btn.classList.add('active');
            
            const tabEl = document.getElementById('tab-' + tabId);
            if (tabEl) tabEl.classList.add('active');
            
            if(tabId === 'visibility') loadOrdersVisibility();
            if(tabId === 'pending-fg' || tabId === 'pending-rm') loadPendingMaterials();

            if(tabId === 'reports') {
                startQRPoll();
            } else {
                if(qrInterval) clearInterval(qrInterval);
            }
        }

        async function fetchQR() {
            try {
                const res = await fetch('api/get_qr.php').then(r => r.json());
                const img = document.getElementById('wa-qr-img');
                const status = document.getElementById('wa-status-text');
                const logoutBtn = document.getElementById('wa-logout-btn');
                
                if (res.success) {
                    if (res.status === 'CONNECTED') {
                        img.style.display = 'none';
                        status.textContent = 'Status: WhatsApp Connected Successfully!';
                        status.style.color = 'var(--success)';
                        logoutBtn.style.display = 'block';
                        if(qrInterval) clearInterval(qrInterval);
                    } else if (res.status === 'QR') {
                        img.style.display = 'block';
                        img.src = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(res.qr);
                        status.textContent = 'Status: Scan the QR code to link your WhatsApp...';
                        status.style.color = 'var(--accent)';
                        logoutBtn.style.display = 'none';
                    }
                } else {
                    img.style.display = 'none';
                    status.textContent = 'Status: ' + res.message;
                    status.style.color = 'var(--danger)';
                    logoutBtn.style.display = 'none';
                }
            } catch(e) { console.error('Error fetching QR', e); }
        }

        async function logoutWhatsApp() {
            if(!confirm("Are you sure you want to unlink WhatsApp?")) return;
            try {
                const res = await fetch('api/logout_wa.php', { method: 'POST' }).then(r => r.json());
                if(res.success) {
                    alert("Logged out successfully! The background script is restarting. Please wait a few seconds to see the new QR code.");
                    // Restart polling
                    document.getElementById('wa-logout-btn').style.display = 'none';
                    startQRPoll();
                }
            } catch(e) { console.error(e); }
        }

        function startQRPoll() {
            fetchQR();
            if(qrInterval) clearInterval(qrInterval);
            qrInterval = setInterval(fetchQR, 3000);
        }

        // Initialize Form Data
        async function initForm() {
            document.getElementById('po-date').valueAsDate = new Date();
            
            try {
                const [clientsRes, productsRes] = await Promise.all([
                    fetch('api/get_clients.php').then(res => res.json()),
                    fetch('api/get_products.php').then(res => res.json())
                ]);

                if(clientsRes.success) {
                    clientsList = clientsRes.clients;
                    const select = document.getElementById('po-client');
                    clientsList.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.client_name;
                        select.appendChild(opt);
                    });
                }

                if(productsRes.success) {
                    productsList = productsRes.products;
                    addItemRow(); // add first empty row
                }
            } catch (err) {
                console.error('Error loading initial data:', err);
            }
        }

        // Add PO Item Row
        function addItemRow() {
            const container = document.getElementById('po-items-container');
            const row = document.createElement('div');
            row.className = 'po-item-row flex-wrap';
            
            let productOptions = '<option value="">Select Code...</option>';
            productsList.forEach(p => {
                productOptions += `<option value="${p.id}">${p.item_code} - ${p.current_name}</option>`;
            });

            row.innerHTML = `
                <div class="form-group" style="flex: 2; min-width: 250px;">
                    <label>Code Number Search</label>
                    <select class="item-product" required>${productOptions}</select>
                </div>
                <div class="form-group" style="width: 100px; flex: initial;">
                    <label>Boxes</label>
                    <input type="number" class="item-boxes" min="0" value="0">
                </div>
                <div class="form-group" style="width: 100px; flex: initial;">
                    <label>Pieces</label>
                    <input type="number" class="item-pieces" min="0" value="0" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label>Lazer Print</label>
                    <input type="text" class="item-lazer" placeholder="e.g. BELL">
                </div>
                <div class="form-group" style="width: 120px; flex: initial;">
                    <label>Lazer Amt</label>
                    <input type="number" class="item-lazer-amt" min="0" value="0">
                </div>
                <div class="form-group" style="width: 100px; flex: initial; display: flex; align-items: center; margin-bottom: 8px;">
                    <label class="checkbox-label" style="margin: 0; height: 100%;">
                        <input type="checkbox" class="item-urgent"> Urgent
                    </label>
                </div>
                <button type="button" class="remove-btn" onclick="this.parentElement.remove()">X</button>
            `;
            container.appendChild(row);
        }

        function selectAllUrgent() {
            document.querySelectorAll('.item-urgent').forEach(cb => cb.checked = true);
        }

        // Form Submit
        document.getElementById('po-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const items = [];
            document.querySelectorAll('.po-item-row').forEach(row => {
                items.push({
                    product_id: row.querySelector('.item-product').value,
                    boxes: row.querySelector('.item-boxes').value,
                    pieces: row.querySelector('.item-pieces').value,
                    lazer_print: row.querySelector('.item-lazer').value,
                    lazer_print_amount: row.querySelector('.item-lazer-amt').value,
                    is_item_urgent: row.querySelector('.item-urgent').checked ? 1 : 0
                });
            });

            if(items.length === 0) { alert('Add at least one item.'); return; }

            const payload = {
                client_id: document.getElementById('po-client').value,
                order_date: document.getElementById('po-date').value,
                deadline_date: document.getElementById('po-deadline').value,
                items: items,
                is_urgent: items.some(i => i.is_item_urgent) ? 1 : 0
            };
            const poId = document.getElementById('po-id').value;
            if(poId) {
                payload.po_id = poId;
            }

            try {
                const res = await fetch('api/save_purchase_order.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                }).then(r => r.json());

                if(res.success) {
                    alert('PO Saved successfully! Autoallort created.');
                    document.getElementById('po-form').reset();
                    document.getElementById('po-id').value = '';
                    document.getElementById('po-items-container').innerHTML = '';
                    addItemRow();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch(err) { console.error(err); alert('Failed to save PO.'); }
        });

        // Load Visibility Data (Section 2)
        async function loadOrdersVisibility() {
            const container = document.getElementById('orders-visibility-container');
            container.innerHTML = '<p>Loading...</p>';
            try {
                const res = await fetch('api/get_purchase_orders.php').then(r => r.json());
                if(res.success) {
                    let html = '';
                    res.orders.forEach(order => {
                        html += `
                        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px; border: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; align-items: center;">
                                <div>
                                    <strong style="color: var(--text-main); font-size: 16px;">PURCHASE ORDER #${order.id}</strong><br>
                                    <span style="color: var(--text-muted); font-size: 14px;">Client: ${order.client_name}</span>
                                </div>
                                <div>
                                    <span style="color: var(--text-muted); font-size: 13px; margin-right: 12px;">Date: ${order.order_date}</span>
                                    ${userRole !== 'Sales Executive' ? `
                                        <button class="btn-outline" onclick='editOrder(${JSON.stringify(order).replace(/'/g, "\\'")})' style="padding: 4px 12px; margin-right: 4px;">Edit</button>
                                        <button onclick="deleteOrder(${order.id})" style="padding: 4px 12px; background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5;">Delete</button>
                                    ` : ''}
                                </div>
                            </div>`;
                        
                        // Normal Items
                        if(order.normal_items.length > 0) {
                            html += `<table style="margin-bottom: 16px;">
                                <thead><tr><th>Code</th><th>Name</th><th>Boxes</th><th>Pieces</th></tr></thead>
                                <tbody>`;
                            order.normal_items.forEach(item => {
                                html += `<tr>
                                    <td>${item.item_code}</td>
                                    <td>${item.current_name}</td>
                                    <td>${item.boxes}</td>
                                    <td>${item.pieces}</td>
                                </tr>`;
                            });
                            html += `</tbody></table>`;
                        }

                        // Urgent Items
                        if(order.urgent_items.length > 0) {
                            html += `<h4 style="color: var(--danger); margin-bottom: 8px;">URGENT ITEMS</h4>
                            <table>
                                <thead><tr><th>Code</th><th>Name</th><th>Boxes</th><th>Pieces</th><th>Status</th></tr></thead>
                                <tbody>`;
                            order.urgent_items.forEach(item => {
                                html += `<tr>
                                    <td>${item.item_code}</td>
                                    <td>${item.current_name}</td>
                                    <td>${item.boxes}</td>
                                    <td>${item.pieces}</td>
                                    <td><span class="badge badge-urgent">URGENT</span></td>
                                </tr>`;
                            });
                            html += `</tbody></table>`;
                        }
                        html += `</div>`;
                    });
                    container.innerHTML = html || '<p>No orders found.</p>';
                }
            } catch(e) { container.innerHTML = '<p style="color:red;">Error loading visibility data.</p>'; }
        }

        function editOrder(order) {
            document.getElementById('po-id').value = order.id;
            document.getElementById('po-client').value = order.client_id;
            document.getElementById('po-date').value = order.order_date;
            document.getElementById('po-deadline').value = order.deadline_date || '';
            
            document.getElementById('po-items-container').innerHTML = '';
            
            const allItems = [...order.normal_items, ...order.urgent_items];
            if (allItems.length === 0) {
                addItemRow();
            } else {
                allItems.forEach(item => {
                    addItemRow();
                    const container = document.getElementById('po-items-container');
                    const lastRow = container.lastElementChild;
                    
                    lastRow.querySelector('.item-product').value = item.product_id;
                    lastRow.querySelector('.item-boxes').value = item.boxes;
                    lastRow.querySelector('.item-pieces').value = item.pieces;
                    lastRow.querySelector('.item-lazer').value = item.lazer_print || '';
                    lastRow.querySelector('.item-lazer-amt').value = item.lazer_print_amount || 0;
                    lastRow.querySelector('.item-urgent').checked = item.is_item_urgent == 1;
                });
            }
            
            // Switch to Create PO tab
            switchTab('create');
            window.scrollTo(0,0);
        }

        async function deleteOrder(poId) {
            if(!confirm('Are you sure you want to delete this Purchase Order? This will also remove any auto-allocated queues.')) return;
            try {
                const res = await fetch('api/delete_purchase_order.php?id=' + poId).then(r => r.json());
                if(res.success) {
                    alert('Order deleted.');
                    loadOrdersVisibility();
                    loadDashboardData();
                } else {
                    alert('Error: ' + res.message);
                }
            } catch(e) {
                alert('Failed to delete order.');
            }
        }

        // Load Pending Data (Sections 3 & 4)
        async function loadPendingMaterials() {
            try {
                const res = await fetch('api/get_pending_raw_materials.php').then(r => r.json());
                if(res.success) {
                    // Section 3: Total Pending FG
                    const tbodyTotalFg = document.querySelector('#table-total-pending-fg tbody');
                    tbodyTotalFg.innerHTML = '';
                    res.total_pending_fg.forEach(row => {
                        tbodyTotalFg.innerHTML += `<tr>
                            <td>${row.code}</td>
                            <td>${row.name}</td>
                            <td>${row.amount}</td>
                            <td>${row.lazer_print || '-'}</td>
                            <td>${row.lazer_amount || 0}</td>
                        </tr>`;
                    });

                    // Section 3: Client Wise FG
                    const clientFgCont = document.getElementById('client-pending-fg-container');
                    clientFgCont.innerHTML = '';
                    for (const [clientName, fgs] of Object.entries(res.client_pending_fg)) {
                        let tableHTML = `<h4 style="color: var(--accent); margin-top: 24px;">CLIENT: ${clientName}</h4>
                        <table><thead><tr><th>Code</th><th>Name</th><th>Amount</th></tr></thead><tbody>`;
                        fgs.forEach(fg => {
                            tableHTML += `<tr><td>${fg.code}</td><td>${fg.name}</td><td>${fg.amount}</td></tr>`;
                        });
                        tableHTML += `</tbody></table>`;
                        clientFgCont.innerHTML += tableHTML;
                    }

                    // Section 4: Total Pending RM
                    const tbodyTotalRm = document.querySelector('#table-total-pending-rm tbody');
                    tbodyTotalRm.innerHTML = '';
                    res.total_pending_rm.forEach(rm => {
                        if(rm.components.length === 0) return;
                        rm.components.forEach((comp, index) => {
                            tbodyTotalRm.innerHTML += `<tr>
                                <td>${index === 0 ? rm.fg_name : ''}</td>
                                <td>${comp.component_name}</td>
                                <td><span class="badge" style="background: #e2e8f0; color: var(--text-main);">${comp.process}</span></td>
                                <td>${comp.amount}</td>
                            </tr>`;
                        });
                    });

                    // Section 4: Client Wise RM
                    const clientRmCont = document.getElementById('client-pending-rm-container');
                    clientRmCont.innerHTML = '';
                    for (const [clientName, rms] of Object.entries(res.client_pending_rm)) {
                        let tableHTML = `<h4 style="color: var(--accent); margin-top: 24px;">CLIENT: ${clientName}</h4>
                        <table><thead><tr><th>F.G Name</th><th>Component</th><th>Amount</th></tr></thead><tbody>`;
                        Object.values(rms).forEach(rm => {
                            if(rm.components.length === 0) return;
                            rm.components.forEach((comp, index) => {
                                tableHTML += `<tr>
                                    <td>${index === 0 ? rm.fg_name : ''}</td>
                                    <td>${comp.component_name} (${comp.process})</td>
                                    <td>${comp.amount}</td>
                                </tr>`;
                            });
                        });
                        tableHTML += `</tbody></table>`;
                        clientRmCont.innerHTML += tableHTML;
                    }

                }
            } catch(e) { console.error('Error loading pending data', e); }
        }

        // Run Init
        window.addEventListener('DOMContentLoaded', initForm);



        // Export to Excel (reusing function name for existing calls)
        function exportTableToCSV(containerId, filename) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            let tables = [];
            if (container.tagName.toUpperCase() === 'TABLE') {
                tables = [container];
            } else {
                tables = Array.from(container.querySelectorAll('table'));
            }
            
            if (tables.length === 0) {
                alert("No table data found to export.");
                return;
            }

            const wb = XLSX.utils.book_new();
            let combinedData = [];
            
            tables.forEach((table, index) => {
                // If there is an H4 or strong header right before the table, let's grab it for context
                let prev = table.previousElementSibling;
                let headerText = '';
                if (prev) {
                    if (prev.tagName === 'H4' || prev.tagName === 'H2') headerText = prev.innerText;
                }
                
                // For Orders visibility, the title is further up in the DOM tree
                let parentDiv = table.closest('div[style*="padding: 16px"]');
                if (parentDiv && !headerText) {
                    let strongTag = parentDiv.querySelector('strong');
                    let spanTag = parentDiv.querySelector('span');
                    if (strongTag) {
                        headerText = strongTag.innerText;
                        if(spanTag) headerText += " - " + spanTag.innerText;
                    }
                }
                
                if (headerText) {
                    combinedData.push([headerText]);
                }
                
                const ws = XLSX.utils.table_to_sheet(table);
                const data = XLSX.utils.sheet_to_json(ws, {header: 1});
                
                combinedData = combinedData.concat(data);
                combinedData.push([]); // Empty row for spacing
            });
            
            const ws = XLSX.utils.aoa_to_sheet(combinedData);
            
            // Auto-size columns loosely based on text length
            const colWidths = [];
            combinedData.forEach(row => {
                row.forEach((cell, colIdx) => {
                    const len = (cell !== undefined && cell !== null) ? String(cell).length : 0;
                    if (!colWidths[colIdx]) colWidths[colIdx] = { wch: 10 };
                    if (len + 2 > colWidths[colIdx].wch) {
                        colWidths[colIdx].wch = len + 2;
                    }
                });
            });
            ws['!cols'] = colWidths;
            
            XLSX.utils.book_append_sheet(wb, ws, "ExportData");
            
            // Output as real Excel file (.xlsx)
            XLSX.writeFile(wb, filename.replace('.csv', '.xlsx'));
        }
    </script>
</body>
</html>
