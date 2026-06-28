<?php
require_once '../includes/auth.php';
requireLogin();
requireRole(['Purchase Order Manager', 'Super Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $client_name = trim($_POST['client_name'] ?? '');

    if (!$id || empty($client_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing ID or Client Name']);
        exit;
    }

    try {
        global $pdo;
        
        // Check duplicate name
        $check = $pdo->prepare('SELECT id FROM clients WHERE client_name = ? AND id != ?');
        $check->execute([$client_name, $id]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Another client with this name already exists']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE clients SET client_name = ? WHERE id = ?');
        $stmt->execute([$client_name, $id]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
