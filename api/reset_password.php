<?php
require '../includes/auth.php';
requireLogin();
requireRole('Super Admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin_users.php');
    exit;
}

$user_id = $_POST['user_id'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (!$user_id || !$new_password) {
    header('Location: ../admin_users.php?error=Missing+fields');
    exit;
}

try {
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    global $pdo;
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, reset_requested = 0 WHERE id = ?');
    $stmt->execute([$hash, $user_id]);
    
    header('Location: ../admin_users.php?success=Password+reset+successfully');
} catch (Exception $e) {
    header('Location: ../admin_users.php?error=Database+error');
}
