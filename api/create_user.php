<?php
require_once '../includes/auth.php';
requireLogin();
requireRole('Super Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';

    if (empty($username) || empty($password) || empty($role_id)) {
        header('Location: ../admin_users.php?error=All fields are required');
        exit;
    }

    try {
        global $pdo;
        
        // Check if username already exists
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $checkStmt->execute([$username]);
        if ($checkStmt->fetch()) {
            header('Location: ../admin_users.php?error=Username already exists');
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hashed_password, $role_id]);

        header('Location: ../admin_users.php?success=1');
        exit;
    } catch (\PDOException $e) {
        header('Location: ../admin_users.php?error=' . urlencode('Database error: ' . $e->getMessage()));
        exit;
    }
} else {
    http_response_code(405);
    echo "Method Not Allowed";
}
