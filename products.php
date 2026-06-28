<?php
require_once 'includes/auth.php';
requireLogin();

global $pdo;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalStmt = $pdo->query('SELECT COUNT(*) FROM products');
$totalProducts = $totalStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

$stmt = $pdo->prepare('SELECT * FROM products ORDER BY id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Manufacturing ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="p-6 md:p-10">

    <header class="mb-10 flex justify-between items-center card p-6">
        <div>
            <h1 class="text-3xl tracking-wide">Manage <span class="text-indigo-600">Products</span></h1>
            <p class="text-sm mt-1">Add, Edit, and Remove Products</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="btn">Back to Dashboard</a>
            <a href="clients.php" class="btn border-blue-500 text-blue-600 hover:bg-blue-50">Clients</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Add Product Form -->
        <div class="xl:col-span-4 flex flex-col gap-8">
            <section class="card p-6">
                <h2 class="text-xl mb-6">Add New Product</h2>
                <form id="add-product-form" action="api/add_product.php" method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="item_code" class="text-xs uppercase font-medium">Item Code</label>
                        <input type="text" id="item_code" name="item_code" class="form-input w-full" placeholder="e.g. PRD-01" required>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="current_name" class="text-xs uppercase font-medium">Product Name</label>
                        <input type="text" id="current_name" name="current_name" class="form-input w-full" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Add Product</button>
                </form>
            </section>
        </div>

        <!-- Existing Products Table -->
        <div class="xl:col-span-8 flex flex-col gap-8">
            <section class="card p-6 overflow-hidden">
                <h2 class="text-xl mb-4">Existing Products</h2>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Code</th>
                                <th>Product Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td class="font-medium"><?= htmlspecialchars($product['item_code']) ?></td>
                                    <td><?= htmlspecialchars($product['current_name']) ?></td>
                                    <td>
                                        <button onclick="editProduct(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['item_code'])) ?>', '<?= htmlspecialchars(addslashes($product['current_name'])) ?>')" class="text-indigo-600 hover:underline mr-3 text-sm font-medium">Edit</button>
                                        <button onclick="deleteProduct(<?= $product['id'] ?>)" class="text-red-600 hover:underline text-sm font-medium">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($products)): ?>
                                <tr><td colspan="4" class="text-center opacity-50">No products found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($totalPages > 1): ?>
                <div class="mt-4 flex justify-between items-center text-sm text-gray-600">
                    <div>Showing Page <?= $page ?> of <?= $totalPages ?></div>
                    <div class="flex gap-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="btn border border-gray-300 bg-white hover:bg-gray-50 py-1 px-3 text-xs">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="btn border border-gray-300 bg-white hover:bg-gray-50 py-1 px-3 text-xs">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        document.getElementById('add-product-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/add_product.php', { method: 'POST', body: formData }).then(r => r.json());
                if (res.success) {
                    await Swal.fire('Added!', 'Product added successfully.', 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Error', res.error || 'Failed to add product', 'error');
                }
            } catch (err) { Swal.fire('Error', 'Error adding product', 'error'); }
        });

        async function editProduct(id, oldCode, oldName) {
            const { value: formValues } = await Swal.fire({
                title: 'Edit Product',
                html: 
                    '<div class="flex flex-col gap-2 text-left mb-3"><label class="text-xs uppercase font-medium">Item Code</label><input id="swal-input1" class="swal2-input mx-0 mt-1" value="' + oldCode + '"></div>' +
                    '<div class="flex flex-col gap-2 text-left"><label class="text-xs uppercase font-medium">Product Name</label><input id="swal-input2" class="swal2-input mx-0 mt-1" value="' + oldName + '"></div>',
                focusConfirm: false,
                showCancelButton: true,
                preConfirm: () => {
                    return [
                        document.getElementById('swal-input1').value,
                        document.getElementById('swal-input2').value
                    ];
                }
            });

            if (formValues) {
                const newCode = formValues[0].trim();
                const newName = formValues[1].trim();
                
                if ((newCode !== "" || newName !== "") && (newCode !== oldCode || newName !== oldName)) {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('item_code', newCode || oldCode);
                    formData.append('current_name', newName || oldName);
                    
                    try {
                        const res = await fetch('api/edit_product.php', { method: 'POST', body: formData }).then(r => r.json());
                        if (res.success) {
                            await Swal.fire('Saved!', 'Product updated successfully.', 'success');
                            window.location.reload();
                        } else {
                            Swal.fire('Error', res.error || 'Failed to edit product', 'error');
                        }
                    } catch (err) { Swal.fire('Error', 'Error editing product', 'error'); }
                }
            }
        }

        async function deleteProduct(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete all associated PO items and raw material formulas!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                try {
                    const res = await fetch('api/delete_product.php', { method: 'POST', body: formData }).then(r => r.json());
                    if (res.success) {
                        await Swal.fire('Deleted!', 'Product has been deleted.', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Error', res.error || 'Failed to delete product', 'error');
                    }
                } catch (err) { Swal.fire('Error', 'Error deleting product', 'error'); }
            }
        }
    </script>
</body>
</html>
