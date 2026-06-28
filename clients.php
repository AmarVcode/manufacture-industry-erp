<?php
require_once 'includes/auth.php';
requireLogin();

global $pdo;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalStmt = $pdo->query('SELECT COUNT(*) FROM clients');
$totalClients = $totalStmt->fetchColumn();
$totalPages = ceil($totalClients / $perPage);

$stmt = $pdo->prepare('SELECT * FROM clients ORDER BY id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - Manufacturing ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="p-6 md:p-10">

    <header class="mb-10 flex justify-between items-center card p-6">
        <div>
            <h1 class="text-3xl tracking-wide">Manage <span class="text-[var(--primary-color)]">Clients</span></h1>
            <p class="text-sm mt-1">Add, Edit, and Remove Clients</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="btn">Back to Dashboard</a>
            <a href="products.php" class="btn border-indigo-500 text-indigo-600 hover:bg-indigo-50">Products</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Add Client Form -->
        <div class="xl:col-span-4 flex flex-col gap-8">
            <section class="card p-6">
                <h2 class="text-xl mb-6">Add New Client</h2>
                <form id="add-client-form" action="api/add_client.php" method="POST" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label for="client_name" class="text-xs uppercase font-medium">Client Name</label>
                        <input type="text" id="client_name" name="client_name" class="form-input w-full" required>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Add Client</button>
                </form>
            </section>
        </div>

        <!-- Existing Clients Table -->
        <div class="xl:col-span-8 flex flex-col gap-8">
            <section class="card p-6 overflow-hidden">
                <h2 class="text-xl mb-4">Existing Clients</h2>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Client Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clients as $client): ?>
                                <tr>
                                    <td><?= $client['id'] ?></td>
                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                    <td>
                                        <button onclick="editClient(<?= $client['id'] ?>, '<?= htmlspecialchars(addslashes($client['client_name'])) ?>')" class="text-blue-600 hover:underline mr-3 text-sm font-medium">Edit</button>
                                        <button onclick="deleteClient(<?= $client['id'] ?>)" class="text-red-600 hover:underline text-sm font-medium">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($clients)): ?>
                                <tr><td colspan="3" class="text-center opacity-50">No clients found.</td></tr>
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
        document.getElementById('add-client-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api/add_client.php', { method: 'POST', body: formData }).then(r => r.json());
                if (res.success) {
                    await Swal.fire('Added!', 'Client added successfully.', 'success');
                    window.location.reload();
                } else {
                    Swal.fire('Error', res.error || 'Failed to add client', 'error');
                }
            } catch (err) { Swal.fire('Error', 'Error adding client', 'error'); }
        });

        async function editClient(id, oldName) {
            const { value: formValues } = await Swal.fire({
                title: 'Edit Client',
                html: '<div class="flex flex-col gap-2 text-left"><label class="text-xs uppercase font-medium">Client Name</label><input id="swal-input1" class="swal2-input mx-0 mt-1" value="' + oldName + '"></div>',
                focusConfirm: false,
                showCancelButton: true,
                preConfirm: () => {
                    return document.getElementById('swal-input1').value;
                }
            });

            if (formValues) {
                const newName = formValues.trim();
                if (newName !== "" && newName !== oldName) {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('client_name', newName);
                    try {
                        const res = await fetch('api/edit_client.php', { method: 'POST', body: formData }).then(r => r.json());
                        if (res.success) {
                            await Swal.fire('Saved!', 'Client updated successfully.', 'success');
                            window.location.reload();
                        } else {
                            Swal.fire('Error', res.error || 'Failed to edit client', 'error');
                        }
                    } catch (err) { Swal.fire('Error', 'Error editing client', 'error'); }
                }
            }
        }

        async function deleteClient(id) {
            const result = await Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete all associated purchase orders!",
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
                    const res = await fetch('api/delete_client.php', { method: 'POST', body: formData }).then(r => r.json());
                    if (res.success) {
                        await Swal.fire('Deleted!', 'Client has been deleted.', 'success');
                        window.location.reload();
                    } else {
                        Swal.fire('Error', res.error || 'Failed to delete client', 'error');
                    }
                } catch (err) { Swal.fire('Error', 'Error deleting client', 'error'); }
            }
        }
    </script>
</body>
</html>
