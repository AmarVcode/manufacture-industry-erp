<?php
// api/add_client.php
require_once '../includes/auth.php';
requireLogin();
requireRole(['Purchase Order Manager', 'Super Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name = trim($_POST['client_name'] ?? '');

    if (empty($client_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Client name is required']);
        exit;
    }

    try {
        global $pdo;
        
        // Check if exists
        $check = $pdo->prepare('SELECT id FROM clients WHERE client_name = ?');
        $check->execute([$client_name]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Client already exists']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO clients (client_name) VALUES (?)');
        $stmt->execute([$client_name]);
        
        echo json_encode(['success' => true, 'message' => 'Client added successfully']);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
