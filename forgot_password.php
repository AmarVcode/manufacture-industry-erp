<?php
require_once 'includes/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    if ($username) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $update = $pdo->prepare('UPDATE users SET reset_requested = 1 WHERE username = ?');
            $update->execute([$username]);
            $message = 'Password reset requested! An admin will review and reset your password soon.';
        } else {
            $error = 'Username not found.';
        }
    } else {
        $error = 'Please enter your username.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Manufacturing ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="flex items-center justify-center min-h-screen p-6">
    <div class="card p-10 w-full max-w-md">
        <h1 class="text-3xl tracking-wide text-center mb-2">Manufacturing <span class="text-[var(--accent-neon)]">ERP</span></h1>
        <p class="text-center text-sm mb-8 opacity-70">Request a Password Reset</p>
        
        <?php if ($error): ?>
            <div class="bg-[rgba(255,0,60,0.1)] border border-[var(--accent-danger)] text-[var(--accent-danger)] p-3 rounded mb-6 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="bg-[rgba(0,255,100,0.1)] border border-[var(--accent-primary)] text-[var(--accent-primary)] p-3 rounded mb-6 text-sm">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php else: ?>
            <form method="POST" action="forgot_password.php" class="flex flex-col gap-6">
                <div class="flex flex-col gap-2">
                    <label for="username" class="text-xs uppercase font-medium">Username</label>
                    <input type="text" id="username" name="username" class="form-input w-full" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary mt-2">Request Reset</button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center">
            <a href="login.php" class="text-sm text-[var(--text-muted)] hover:underline">Back to Login</a>
        </div>
    </div>
</body>
</html>
