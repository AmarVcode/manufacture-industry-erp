<?php
require '../includes/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, item_code, current_name FROM products ORDER BY current_name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'products' => $products]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
