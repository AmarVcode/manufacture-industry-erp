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
});

let globalProducts = [];

async function fetchDashboardData() {
    try {
        const response = await fetch('api/get_dashboard_data.php');
        
        // In a real app we handle 401/403 redirects here. For now, assume success or error
        if (response.status === 403 || response.status === 401) {
            document.body.innerHTML = '<div class="p-10 text-center"><h1 class="text-2xl text-red-500">Access Denied. Please Login.</h1></div>';
            return;
        }

        const res = await response.json();
        
        if (res.success) {
            const data = res.data;
            globalProducts = data.products;

            // Populate Dropdowns
            populateSelect('client_id', data.clients, 'id', 'client_name');
            populateSelect('filter_client', data.clients, 'id', 'client_name');
            
            // Populate initial product row
            const firstProductSelect = document.querySelector('.item-product');
            if (firstProductSelect) {
                populateSelectElement(firstProductSelect, data.products, 'id', 'current_name', 'item_code');
            }

            // Populate Tables
            renderCombinedTotals(data.combined_totals);
            renderRawMaterials(data.raw_material_pendings);

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

function renderCombinedTotals(data) {
    const tbody = document.getElementById('table-combined-totals');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center opacity-50">No active products found.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="font-medium">${row.item_code}</td>
            <td>${row.current_name}</td>
            <td>${row.total_boxes}</td>
            <td>${row.total_pieces}</td>
        `;
        tbody.appendChild(tr);
    });
}

function renderRawMaterials(data) {
    const tbody = document.getElementById('table-raw-materials');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center opacity-50">No pending raw materials.</td></tr>';
        return;
    }

    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="px-2 py-1 bg-[rgba(255,255,255,0.1)] rounded text-xs">${row.department_name}</span></td>
            <td class="font-medium text-[var(--accent-neon)]">${row.item_code}</td>
            <td>${row.item_name}</td>
            <td class="font-bold">${row.total_required}</td>
        `;
        tbody.appendChild(tr);
    });
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
    window.location.href = 'login.php';
}
