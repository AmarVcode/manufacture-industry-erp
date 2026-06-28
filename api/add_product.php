<?php
// api/add_product.php
require_once '../includes/auth.php';
requireLogin();
requireRole(['Super Admin', 'Production Floor Manager']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_code = trim($_POST['item_code'] ?? '');
    $current_name = trim($_POST['current_name'] ?? '');

    if (empty($item_code) || empty($current_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Item code and product name are required']);
        exit;
    }

    try {
        global $pdo;
        
        // Check if exists
        $check = $pdo->prepare('SELECT id FROM products WHERE item_code = ?');
        $check->execute([$item_code]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Product with this item code already exists']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO products (item_code, current_name) VALUES (?, ?)');
        $stmt->execute([$item_code, $current_name]);
        
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
