<?php
require_once 'includes/auth.php';
requireLogin();
requireRole('Super Admin');

// Fetch all roles for the dropdown
global $pdo;
$rolesStmt = $pdo->query('SELECT * FROM roles ORDER BY role_name');
$roles = $rolesStmt->fetchAll();

// Fetch all users for the table
$usersStmt = $pdo->query('SELECT u.id, u.username, u.reset_requested, r.role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.reset_requested DESC, u.id DESC');
$users = $usersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Manufacturing ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="p-6 md:p-10">

    <header class="mb-10 flex justify-between items-center card p-6">
        <div>
            <h1 class="text-3xl tracking-wide">User Management <span class="text-[var(--accent-neon)]">Admin</span></h1>
            <p class="text-sm mt-1">Create and Manage ERP Accounts</p>
        </div>
        <div class="flex items-center gap-4">
            <a href="index.php" class="btn">Back to Dashboard</a>
            <a href="clients.php" class="btn border-blue-500 text-blue-600 hover:bg-blue-50">Clients</a>
            <a href="products.php" class="btn border-indigo-500 text-indigo-600 hover:bg-indigo-50">Products</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
    </header>

    <main class="grid grid-cols-1 xl:grid-cols-12 gap-8">
        
        <!-- Create User Form -->
        <div class="xl:col-span-4 flex flex-col gap-8">
            <section class="card p-6">
                <h2 class="text-xl mb-6">Create New User</h2>
                <form id="create-user-form" action="api/create_user.php" method="POST" class="flex flex-col gap-4">
                    
                    <div class="flex flex-col gap-1">
                        <label for="username" class="text-xs uppercase">Username</label>
                        <input type="text" id="username" name="username" class="form-input w-full" required>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="password" class="text-xs uppercase">Password</label>
                        <input type="password" id="password" name="password" class="form-input w-full" required>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label for="role_id" class="text-xs uppercase">Role</label>
                        <select id="role_id" name="role_id" class="form-input w-full" required>
                            <option value="" disabled selected>Select Role...</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (isset($_GET['success'])): ?>
                        <div class="text-green-600 text-sm mt-2">User created successfully!</div>
                    <?php elseif (isset($_GET['error'])): ?>
                        <div class="text-[var(--accent-danger)] text-sm mt-2"><?= htmlspecialchars($_GET['error']) ?></div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary mt-4 w-full">Create User</button>
                </form>
            </section>
        </div>

        <!-- Existing Users Table -->
        <div class="xl:col-span-8 flex flex-col gap-8">
            <section class="card p-6 overflow-hidden">
                <h2 class="text-xl mb-4">Existing Users</h2>
                <div class="overflow-x-auto">
                    <table class="data-table min-w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Reset Password</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr class="<?= $user['reset_requested'] ? 'bg-red-50' : '' ?>">
                                    <td><?= $user['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($user['username']) ?>
                                        <?php if($user['reset_requested']): ?>
                                            <span class="badge badge-urgent ml-2" style="font-size:10px;">Requested Reset</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($user['role_name']) ?></td>
                                    <td><?= $user['reset_requested'] ? '<span class="text-red-500 font-bold">Needs Reset</span>' : '<span class="text-green-600">OK</span>' ?></td>
                                    <td>
                                        <form method="POST" action="api/reset_password.php" class="flex gap-2">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="text" name="new_password" placeholder="New Password" class="form-input text-xs w-32" required>
                                            <button type="submit" class="btn btn-primary text-xs py-1 px-2">Set</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="5" class="text-center opacity-50">No users found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

</body>
</html>
