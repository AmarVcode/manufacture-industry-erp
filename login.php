<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (loginUser($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Manufacturing ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="flex items-center justify-center min-h-screen p-6">
    <div class="card p-10 w-full max-w-md">
        <h1 class="text-3xl tracking-wide text-center mb-2">Manufacturing <span class="text-[var(--accent-neon)]">ERP</span></h1>
        <p class="text-center text-sm mb-8 opacity-70">Log in to your account</p>
        
        <?php if ($error): ?>
            <div class="bg-[rgba(255,0,60,0.1)] border border-[var(--accent-danger)] text-[var(--accent-danger)] p-3 rounded mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <label for="username" class="text-xs uppercase font-medium">Username</label>
                <input type="text" id="username" name="username" class="form-input w-full" required autofocus>
            </div>
            
            <div class="flex flex-col gap-2">
                <label for="password" class="text-xs uppercase font-medium">Password</label>
                <input type="password" id="password" name="password" class="form-input w-full" required>
            </div>
            
            <button type="submit" class="btn btn-primary mt-2">Log In</button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="forgot_password.php" class="text-sm text-[var(--accent-primary)] hover:underline">Forgot Password?</a>
        </div>
    </div>
</body>
</html>
