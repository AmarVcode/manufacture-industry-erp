<?php
// includes/auth.php
session_start();

require_once 'db.php';

function loginUser($username, $password) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Using simple password verification for demonstration. In production use password_verify()
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_name'] = $user['role_name'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function hasRole($allowed_roles) {
    if (!isLoggedIn()) return false;
    $user_role = $_SESSION['role_name'];
    
    // Admin sees everything
    if ($user_role === 'Super Admin') return true;

    if (is_array($allowed_roles)) {
        return in_array($user_role, $allowed_roles);
    }
    return $user_role === $allowed_roles;
}

function requireRole($allowed_roles) {
    if (!hasRole($allowed_roles)) {
        http_response_code(403);
        echo "403 Forbidden: You do not have permission to view this department queue.";
        exit;
    }
}
?>
