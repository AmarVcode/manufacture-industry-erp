<?php
require '../includes/auth.php';
require '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'PO ID missing']);
    exit;
}

if ($_SESSION['role_name'] === 'Sales Executive') {
    echo json_encode(['success' => false, 'message' => 'Sales Executives are not allowed to delete orders.']);
    exit;
}

$po_id = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // Delete from department_queues
    $stmt = $pdo->prepare("DELETE FROM department_queues WHERE po_id = ?");
    $stmt->execute([$po_id]);

    // Delete from po_items
    $stmt = $pdo->prepare("DELETE FROM po_items WHERE po_id = ?");
    $stmt->execute([$po_id]);

    // Delete from purchase_orders
    $stmt = $pdo->prepare("DELETE FROM purchase_orders WHERE id = ?");
    $stmt->execute([$po_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
