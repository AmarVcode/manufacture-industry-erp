<?php
require '../includes/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, client_name FROM clients ORDER BY client_name ASC");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'clients' => $clients]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
