<?php
// api/submit_order.php
require_once '../includes/auth.php';
requireLogin();
requireRole(['Purchase Order Manager', 'Super Admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $order_date = $_POST['order_date'] ?? date('Y-m-d');
    $deadline_date = $_POST['deadline_date'] ?? null;
    $items = $_POST['items'] ?? []; // Array of associative arrays [product_id, boxes, pieces, is_urgent]
    $is_order_urgent = $_POST['is_urgent'] ?? false;

    if (!$client_id || empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Create Purchase Order
        $stmt = $pdo->prepare('INSERT INTO purchase_orders (client_id, order_date, deadline_date, is_urgent) VALUES (?, ?, ?, ?)');
        $stmt->execute([$client_id, $order_date, $deadline_date, $is_order_urgent ? 1 : 0]);
        $po_id = $pdo->lastInsertId();

        $total_boxes_all = 0;
        $total_pieces_all = 0;

        foreach ($items as $item) {
            $product_id = $item['product_id'];
            $boxes = (int)($item['boxes'] ?? 0);
            $pieces = (int)($item['pieces'] ?? 0);
            $is_item_urgent = $item['is_urgent'] ?? false;

            $total_boxes_all += $boxes;
            $total_pieces_all += $pieces;

            // Assuming 1 Box = 100 Pieces for calculation purposes if no explicit mapping exists
            $total_units_required = ($boxes * 100) + $pieces;

            // Insert PO Item
            $stmt_po = $pdo->prepare('INSERT INTO po_items (po_id, product_id, boxes, pieces, is_item_urgent) VALUES (?, ?, ?, ?, ?)');
            $stmt_po->execute([$po_id, $product_id, $boxes, $pieces, $is_item_urgent ? 1 : 0]);

            // Get product code
            $stmt_prod = $pdo->prepare('SELECT item_code, current_name FROM products WHERE id = ?');
            $stmt_prod->execute([$product_id]);
            $product = $stmt_prod->fetch();

            if ($product) {
                // Apply Raw Material Conversion Algorithm
                $stmt_conv = $pdo->prepare('SELECT * FROM raw_material_conversion WHERE parent_product_code = ?');
                $stmt_conv->execute([$product['item_code']]);
                $conversions = $stmt_conv->fetchAll();

                foreach ($conversions as $conv) {
                    $multiplier = (float)$conv['exact_multiplier_qty'];
                    $qty_needed = $total_units_required * $multiplier;
                    $dept_name = '';

                    switch ($conv['process_type']) {
                        case 'moulding': $dept_name = 'Moulding Supervisor'; break;
                        case 'brasspart': $dept_name = 'Brasspart Supervisor'; break;
                        case 'packaging': $dept_name = 'Packaging Supervisor'; break;
                    }

                    if ($dept_name) {
                        // Push calculated breakdown to department queue
                        $stmt_queue = $pdo->prepare('INSERT INTO department_queues (po_id, department_name, item_code, item_name, quantity_required) VALUES (?, ?, ?, ?, ?)');
                        $stmt_queue->execute([$po_id, $dept_name, $conv['component_name'], $conv['component_name'], $qty_needed]);
                    }
                }
            }
        }

        // Update totals on PO
        $stmt_update_po = $pdo->prepare('UPDATE purchase_orders SET total_boxes = ?, total_pieces = ? WHERE id = ?');
        $stmt_update_po->execute([$total_boxes_all, $total_pieces_all, $po_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'po_id' => $po_id, 'message' => 'Order submitted and components queued successfully.']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Transaction failed: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
