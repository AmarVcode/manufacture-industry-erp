// js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fetch Initial Dashboard Data
    fetchDashboardData();

    // 2. Setup Form Submission
    const orderForm = document.getElementById('order-form');
    if (orderForm) {
        orderForm.addEventListener('submit', handleOrderSubmission);
    }

    // 3. Setup Add Item Button
    const addItemBtn = document.getElementById('add-item-btn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', cloneItemRow);
    }

    // 4. Setup Quick Add Forms
    const addClientForm = document.getElementById('add-client-form');
    if (addClientForm) {
        addClientForm.addEventListener('submit', handleAddClient);
    }
    const addProductForm = document.getElementById('add-product-form');
    if (addProductForm) {
        addProductForm.addEventListener('submit', handleAddProduct);
    }

    // 5. Setup Apply Filters Button
    const applyFiltersBtn = document.getElementById('apply-filters-btn');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', () => {
            currentTotalsPage = 1;
            currentRawMaterialsPage = 1;
            fetchDashboardData();
        });
    }
});

let globalProducts = [];
let combinedTotalsData = [];
let rawMaterialsData = [];
let recentOrdersData = [];
let currentTotalsPage = 1;
let currentRawMaterialsPage = 1;
let currentRecentOrdersPage = 1;
const ITEMS_PER_PAGE = 5;

async function fetchDashboardData() {
    try {
        const clientFilterElem = document.getElementById('filter_client');
        const typeFilterElem = document.getElementById('filter_type');
        
        let url = 'api/get_dashboard_data.php?';
        if (clientFilterElem && clientFilterElem.value !== 'all') {
            url += `client_id=${clientFilterElem.value}&`;
        }
        if (typeFilterElem && typeFilterElem.value !== 'all') {
            url += `type=${typeFilterElem.value}&`;
        }

        const response = await fetch(url);
        
        // In a real app we handle 401/403 redirects here. For now, assume success or error
        if (response.status === 403 || response.status === 401) {
            document.body.innerHTML = '<div class="p-10 text-center"><h1 class="text-2xl text-red-500">Access Denied. Please Login.</h1></div>';
            return;
        }

        const res = await response.json();
        
        if (res.success) {
            const data = res.data;
            globalProducts = data.products;

            // Populate Dropdowns only if they are currently empty or it's first load
            const clientSelect = document.getElementById('client_id');
            if (clientSelect && clientSelect.options.length <= 1) {
                populateSelect('client_id', data.clients, 'id', 'client_name');
                populateSelect('filter_client', data.clients, 'id', 'client_name');
                // Restore filter selection if it was reset
                if (clientFilterElem) clientFilterElem.value = new URLSearchParams(url.split('?')[1]).get('client_id') || 'all';
            }
            
            // Populate initial product row
            const firstProductSelect = document.querySelector('.item-product');
            if (firstProductSelect) {
                populateSelectElement(firstProductSelect, data.products, 'id', 'current_name', 'item_code');
            }

            // Populate Tables
            combinedTotalsData = data.combined_totals;
            rawMaterialsData = data.raw_material_pendings;
            recentOrdersData = data.recent_orders || [];
            renderCombinedTotals();
            renderRawMaterials();
            renderRecentOrders();

            // Fake User Role display since API doesn't return it currently, let's assume it's sent or we fetch it
            document.getElementById('user-role-display').innerText = `Dashboard Active`;
        } else {
            console.error("Failed to load dashboard data:", res.error);
        }
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    }
}

function populateSelect(selectId, dataArray, valField, textField) {
    const select = document.getElementById(selectId);
    if (!select) return;
    
    // Keep first option
    const firstOption = select.options[0];
    select.innerHTML = '';
    select.appendChild(firstOption);

    dataArray.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valField];
        opt.textContent = item[textField];
        select.appendChild(opt);
    });
}

function populateSelectElement(selectElem, dataArray, valField, textField, codeField) {
    selectElem.innerHTML = '<option value="" disabled selected>Select Product...</option>';
    dataArray.forEach(item => {
        const opt = document.createElement('option');
        opt.value = item[valField];
        opt.textContent = `${item[codeField]} - ${item[textField]}`;
        selectElem.appendChild(opt);
    });
}

function renderCombinedTotals(viewAll = false) {
    const tbody = document.getElementById('table-combined-totals');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (combinedTotalsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center opacity-50">No active products found.</td></tr>';
        return;
    }

    const limit = viewAll ? combinedTotalsData.length : ITEMS_PER_PAGE;
    const totalPages = Math.ceil(combinedTotalsData.length / limit);
    const start = (currentTotalsPage - 1) * limit;
    const paginatedData = combinedTotalsData.slice(start, start + limit);

    paginatedData.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="font-medium">${row.item_code}</td>
            <td>${row.current_name}</td>
            <td>${row.total_boxes}</td>
            <td>${row.total_pieces}</td>
        `;
        tbody.appendChild(tr);
    });

    if (!viewAll) {
        renderPaginationControls(tbody, currentTotalsPage, totalPages, (newPage) => {
            currentTotalsPage = newPage;
            renderCombinedTotals();
        }, () => {
            currentTotalsPage = 1;
            renderCombinedTotals(true);
        });
    }
}

function renderRawMaterials(viewAll = false) {
    const tbody = document.getElementById('table-raw-materials');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (rawMaterialsData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center opacity-50">No pending raw materials.</td></tr>';
        return;
    }

    const limit = viewAll ? rawMaterialsData.length : ITEMS_PER_PAGE;
    const totalPages = Math.ceil(rawMaterialsData.length / limit);
    const start = (currentRawMaterialsPage - 1) * limit;
    const paginatedData = rawMaterialsData.slice(start, start + limit);

    paginatedData.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="px-2 py-1 bg-[rgba(255,255,255,0.1)] rounded text-xs">${row.department_name}</span></td>
            <td class="font-medium text-[var(--accent-neon)]">${row.item_code}</td>
            <td>${row.item_name}</td>
            <td class="font-bold">${row.total_required}</td>
        `;
        tbody.appendChild(tr);
    });

    if (!viewAll) {
        renderPaginationControls(tbody, currentRawMaterialsPage, totalPages, (newPage) => {
            currentRawMaterialsPage = newPage;
            renderRawMaterials();
        }, () => {
            currentRawMaterialsPage = 1;
            renderRawMaterials(true);
        });
    }
}

function renderRecentOrders(viewAll = false) {
    const tbody = document.getElementById('table-recent-orders');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (recentOrdersData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center opacity-50">No recent orders found.</td></tr>';
        return;
    }

    const limit = viewAll ? recentOrdersData.length : ITEMS_PER_PAGE;
    const totalPages = Math.ceil(recentOrdersData.length / limit);
    const start = (currentRecentOrdersPage - 1) * limit;
    const paginatedData = recentOrdersData.slice(start, start + limit);

    paginatedData.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="font-medium">#${row.id}</td>
            <td>${row.client_name}</td>
            <td>${row.order_date}</td>
            <td>
                ${row.is_urgent == 1 
                    ? '<span class="text-red-500 font-bold text-xs uppercase bg-red-100 px-2 py-1 rounded">Urgent</span>'
                    : '<span class="text-gray-500 font-medium text-xs uppercase bg-gray-100 px-2 py-1 rounded">Standard</span>'
                }
            </td>
        `;
        tbody.appendChild(tr);
    });

    if (!viewAll) {
        renderPaginationControls(tbody, currentRecentOrdersPage, totalPages, (newPage) => {
            currentRecentOrdersPage = newPage;
            renderRecentOrders();
        }, () => {
            currentRecentOrdersPage = 1;
            renderRecentOrders(true);
        });
    }
}

function renderPaginationControls(tbody, currentPage, totalPages, onPageChange, onViewAll) {
    if (totalPages <= 1) return;

    const tr = document.createElement('tr');
    const td = document.createElement('td');
    td.colSpan = 4;
    td.innerHTML = `
        <div class="flex justify-between items-center text-sm text-gray-600 mt-2">
            <div>Showing Page ${currentPage} of ${totalPages}</div>
            <div class="flex gap-2 items-center">
                <button class="text-xs text-[var(--accent-neon)] hover:underline mr-2 view-all-btn">Show All Data</button>
                <button class="btn border border-gray-300 bg-white hover:bg-gray-50 py-1 px-3 text-xs prev-btn">Previous</button>
                <button class="btn border border-gray-300 bg-white hover:bg-gray-50 py-1 px-3 text-xs next-btn">Next</button>
            </div>
        </div>
    `;

    const viewAllBtn = td.querySelector('.view-all-btn');
    const prevBtn = td.querySelector('.prev-btn');
    const nextBtn = td.querySelector('.next-btn');

    if (currentPage === 1) prevBtn.style.display = 'none';
    if (currentPage === totalPages) nextBtn.style.display = 'none';

    viewAllBtn.addEventListener('click', () => onViewAll());
    prevBtn.addEventListener('click', () => onPageChange(currentPage - 1));
    nextBtn.addEventListener('click', () => onPageChange(currentPage + 1));

    tbody.appendChild(tr);
}

function cloneItemRow() {
    const container = document.getElementById('order-items-container');
    const firstRow = container.querySelector('.order-item');
    if (!firstRow) return;

    const newRow = firstRow.cloneNode(true);
    
    // Clear inputs in cloned row
    newRow.querySelector('.item-boxes').value = '';
    newRow.querySelector('.item-pieces').value = '';
    newRow.querySelector('.item-product').selectedIndex = 0;

    container.appendChild(newRow);
}

async function handleOrderSubmission(e) {
    e.preventDefault();

    const clientId = document.getElementById('client_id').value;
    const orderDate = document.getElementById('order_date').value;
    const deadlineDate = document.getElementById('deadline_date').value;
    const isUrgent = document.getElementById('is_urgent').checked;

    const itemRows = document.querySelectorAll('.order-item');
    const items = [];

    itemRows.forEach(row => {
        const productId = row.querySelector('.item-product').value;
        const boxes = row.querySelector('.item-boxes').value;
        const pieces = row.querySelector('.item-pieces').value;

        if (productId && (boxes > 0 || pieces > 0)) {
            items.push({
                product_id: productId,
                boxes: boxes || 0,
                pieces: pieces || 0,
                is_urgent: isUrgent
            });
        }
    });

    if (items.length === 0) {
        alert("Please add at least one product with quantities.");
        return;
    }

    const payload = new URLSearchParams();
    payload.append('client_id', clientId);
    payload.append('order_date', orderDate);
    if (deadlineDate) payload.append('deadline_date', deadlineDate);
    payload.append('is_urgent', isUrgent ? 1 : 0);

    items.forEach((item, index) => {
        payload.append(`items[${index}][product_id]`, item.product_id);
        payload.append(`items[${index}][boxes]`, item.boxes);
        payload.append(`items[${index}][pieces]`, item.pieces);
        payload.append(`items[${index}][is_urgent]`, item.is_urgent ? 1 : 0);
    });

    try {
        const response = await fetch('api/submit_order.php', {
            method: 'POST',
            body: payload
        });
        
        const res = await response.json();
        if (res.success) {
            alert(res.message);
            // Reset form and reload data
            e.target.reset();
            // Reset items to just 1 row
            const container = document.getElementById('order-items-container');
            const firstRow = container.querySelector('.order-item').cloneNode(true);
            container.innerHTML = '';
            container.appendChild(firstRow);
            
            fetchDashboardData();
        } else {
            alert("Error: " + res.error);
        }
    } catch (error) {
        console.error("Submission error:", error);
        alert("A system error occurred.");
    }
}

function logout() {
    // Basic logout handling - normally hits a logout.php
    window.location.href = 'logout.php';
}

async function handleAddClient(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('api/add_client.php', { method: 'POST', body: formData });
        const res = await response.json();
        if (res.success) {
            alert(res.message);
            e.target.reset();
            fetchDashboardData(); // Refresh dropdowns
        } else {
            alert("Error: " + res.error);
        }
    } catch (err) {
        alert("Failed to add client.");
    }
}

async function handleAddProduct(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
        const response = await fetch('api/add_product.php', { method: 'POST', body: formData });
        const res = await response.json();
        if (res.success) {
            alert(res.message);
            e.target.reset();
            fetchDashboardData(); // Refresh dropdowns
        } else {
            alert("Error: " + res.error);
        }
    } catch (err) {
        alert("Failed to add product.");
    }
}
