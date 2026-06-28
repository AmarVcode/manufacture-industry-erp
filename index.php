<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturing ERP - Master Dashboard</title>
    <!-- Tailwind CSS (CDN for zero-build setup) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="p-6 md:p-10">

    <header class="mb-10 flex justify-between items-center glass-panel p-6">
        <div>
            <h1 class="text-3xl tracking-wide">Manufacturing <span class="text-[var(--accent-neon)]">ERP</span></h1>
            <p class="text-sm mt-1">Master Control Dashboard</p>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm" id="user-role-display">Role: Loading...</span>
            <button class="glass-btn" onclick="logout()">Logout</button>
        </div>
    </header>

    <main class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Left Column: Interactions & Filters -->
        <div class="xl:col-span-4 flex flex-col gap-8">
            
            <!-- Section 1: Order Creation -->
            <section class="glass-panel p-6">
                <h2 class="text-xl mb-6">Create Order</h2>
                <form id="order-form" class="flex flex-col gap-4">
                    
                    <div class="flex flex-col gap-1">
                        <label for="client_id" class="text-xs uppercase">Client</label>
                        <select id="client_id" name="client_id" class="glass-input w-full" required>
                            <option value="" disabled selected>Select Client...</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1">
                            <label for="order_date" class="text-xs uppercase">Order Date</label>
                            <input type="date" id="order_date" name="order_date" class="glass-input w-full" required>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="deadline_date" class="text-xs uppercase">Deadline</label>
                            <input type="date" id="deadline_date" name="deadline_date" class="glass-input w-full">
                        </div>
                    </div>

                    <div class="flex items-center justify-between p-3 rounded-lg border border-[rgba(255,255,255,0.1)] bg-[rgba(255,255,255,0.02)]">
                        <span class="text-sm font-medium">URGENT ORDER</span>
                        <input type="checkbox" id="is_urgent" name="is_urgent" class="urgent-toggle">
                    </div>

                    <hr class="border-[rgba(255,255,255,0.1)] my-2">

                    <!-- Order Items Dynamic Area -->
                    <div id="order-items-container" class="flex flex-col gap-3">
                        <div class="order-item grid grid-cols-12 gap-2 items-end">
                            <div class="col-span-6 flex flex-col gap-1">
                                <label class="text-[10px] uppercase">Product Code</label>
                                <select class="glass-input w-full text-sm item-product" required></select>
                            </div>
                            <div class="col-span-3 flex flex-col gap-1">
                                <label class="text-[10px] uppercase">Boxes</label>
                                <input type="number" min="0" class="glass-input w-full text-sm item-boxes" placeholder="0">
                            </div>
                            <div class="col-span-3 flex flex-col gap-1">
                                <label class="text-[10px] uppercase">Pieces</label>
                                <input type="number" min="0" class="glass-input w-full text-sm item-pieces" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <button type="button" id="add-item-btn" class="text-xs text-right mt-1 opacity-70 hover:opacity-100 transition-opacity">+ Add Another Item</button>

                    <button type="submit" class="glass-btn btn-primary mt-4 w-full">Submit to Queues</button>
                </form>
            </section>

            <!-- Section 2: Party-Wise Filter -->
            <section class="glass-panel p-6">
                <h2 class="text-xl mb-4">Live Data Filters</h2>
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs uppercase">Filter by Party</label>
                        <select id="filter_client" class="glass-input w-full">
                            <option value="all">All Parties</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs uppercase">Order Type</label>
                        <select id="filter_type" class="glass-input w-full">
                            <option value="all">All Orders</option>
                            <option value="standard">Standard</option>
                            <option value="urgent">Urgent Items Only</option>
                        </select>
                    </div>
                </div>
            </section>

        </div>

        <!-- Right Column: Analytics & Tables -->
        <div class="xl:col-span-8 flex flex-col gap-8">
            
            <!-- Section 3: Combined Totals Master -->
            <section class="glass-panel p-6 overflow-hidden">
                <h2 class="text-xl mb-4">Combined Totals Master <span class="text-sm font-normal opacity-60 ml-2">(Active Products)</span></h2>
                <div class="overflow-x-auto">
                    <table class="glass-table min-w-full">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Product Name</th>
                                <th>Total Boxes</th>
                                <th>Total Pieces</th>
                            </tr>
                        </thead>
                        <tbody id="table-combined-totals">
                            <!-- Populated by JS -->
                            <tr><td colspan="4" class="text-center opacity-50">Loading data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Section 4: Raw Material Pendings -->
            <section class="glass-panel p-6 overflow-hidden">
                <h2 class="text-xl mb-4">Raw Material Pendings <span class="text-sm font-normal opacity-60 ml-2">(Department Queues)</span></h2>
                <div class="overflow-x-auto">
                    <table class="glass-table min-w-full">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Component Code</th>
                                <th>Component Name</th>
                                <th>Required Quantity</th>
                            </tr>
                        </thead>
                        <tbody id="table-raw-materials">
                            <!-- Populated by JS -->
                            <tr><td colspan="4" class="text-center opacity-50">Loading data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </main>

    <script src="js/app.js"></script>
</body>
</html>
