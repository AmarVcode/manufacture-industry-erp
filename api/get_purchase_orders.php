<?php
require '../includes/db.php';
header('Content-Type: application/json');

try {
    // Fetch all POs with client details
    $stmt_pos = $pdo->query("
        SELECT po.*, c.client_name 
        FROM purchase_orders po
        JOIN clients c ON po.client_id = c.id
        ORDER BY po.id DESC
    ");
    $pos = $stmt_pos->fetchAll(PDO::FETCH_ASSOC);

    // Fetch items for all these POs
    $stmt_items = $pdo->query("
        SELECT poi.*, p.item_code, p.current_name 
        FROM po_items poi
        JOIN products p ON poi.product_id = p.id
    ");
    $all_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Group items by po_id
    $items_by_po = [];
    foreach ($all_items as $item) {
        $items_by_po[$item['po_id']][] = $item;
    }

    $orders = [];
    foreach ($pos as $po) {
        $po_id = $po['id'];
        $items = $items_by_po[$po_id] ?? [];
        
        $normal_items = [];
        $urgent_items = [];

        foreach ($items as $item) {
            if ($item['is_item_urgent'] || $po['is_urgent']) {
                $urgent_items[] = $item;
            } else {
                $normal_items[] = $item;
            }
        }

        $po['normal_items'] = $normal_items;
        $po['urgent_items'] = $urgent_items;

        $orders[] = $po;
    }

    echo json_encode(['success' => true, 'orders' => $orders]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
