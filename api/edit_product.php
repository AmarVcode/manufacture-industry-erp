<?php
require_once '../includes/auth.php';
requireLogin();
requireRole(['Production Floor Manager', 'Super Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $item_code = trim($_POST['item_code'] ?? '');
    $current_name = trim($_POST['current_name'] ?? '');

    if (!$id || empty($item_code) || empty($current_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID, Item Code, or Product Name']);
        exit;
    }

    try {
        global $pdo;
        
        // Check duplicate code
        $check = $pdo->prepare('SELECT id FROM products WHERE item_code = ? AND id != ?');
        $check->execute([$item_code, $id]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Another product with this code already exists']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE products SET item_code = ?, current_name = ? WHERE id = ?');
        $stmt->execute([$item_code, $current_name, $id]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
