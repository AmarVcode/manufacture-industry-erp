<?php
require '../includes/auth.php';
require '../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['client_id']) || !isset($data['order_date']) || !isset($data['items']) || !is_array($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    $is_po_urgent = $data['is_urgent'] ?? 0;
    
    $total_boxes = 0;
    $total_pieces = 0;
    foreach ($data['items'] as $item) {
        $total_boxes += (int)$item['boxes'];
        $total_pieces += (int)$item['pieces'];
    }

    if (isset($data['po_id']) && !empty($data['po_id'])) {
        if ($_SESSION['role_name'] === 'Sales Executive') {
            echo json_encode(['success' => false, 'message' => 'Sales Executives are not allowed to edit orders.']);
            exit;
        }

        $po_id = (int)$data['po_id'];
        
        // Update purchase_orders
        $stmt = $pdo->prepare("UPDATE purchase_orders SET client_id=?, order_date=?, deadline_date=?, total_boxes=?, total_pieces=?, is_urgent=? WHERE id=?");
        $stmt->execute([
            $data['client_id'],
            $data['order_date'],
            $data['deadline_date'] ?? null,
            $total_boxes,
            $total_pieces,
            $is_po_urgent,
            $po_id
        ]);

        // Delete existing items and queues
        $pdo->prepare("DELETE FROM department_queues WHERE po_id = ?")->execute([$po_id]);
        $pdo->prepare("DELETE FROM po_items WHERE po_id = ?")->execute([$po_id]);
        
        $is_update = true;
    } else {
        // 1. Insert into purchase_orders
        $stmt = $pdo->prepare("INSERT INTO purchase_orders (client_id, order_date, deadline_date, total_boxes, total_pieces, is_urgent) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['client_id'],
            $data['order_date'],
            $data['deadline_date'] ?? null,
            $total_boxes,
            $total_pieces,
            $is_po_urgent
        ]);
        
        $po_id = $pdo->lastInsertId();
        $is_update = false;
    }

    // 2. Insert into po_items and handle AUTOALLORT (department_queues)
    $stmt_po_item = $pdo->prepare("INSERT INTO po_items (po_id, product_id, boxes, pieces, is_item_urgent, lazer_print, lazer_print_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    // Autoallort preparation
    $stmt_product = $pdo->prepare("SELECT item_code, current_name FROM products WHERE id = ?");
    $stmt_conversion = $pdo->prepare("SELECT process_type, component_name, exact_multiplier_qty FROM raw_material_conversion WHERE parent_product_code = ?");
    $stmt_queue = $pdo->prepare("INSERT INTO department_queues (po_id, department_name, item_code, item_name, quantity_required) VALUES (?, ?, ?, ?, ?)");

    foreach ($data['items'] as $item) {
        $stmt_po_item->execute([
            $po_id,
            $item['product_id'],
            $item['boxes'],
            $item['pieces'],
            $item['is_item_urgent'] ?? 0,
            $item['lazer_print'] ?? null,
            $item['lazer_print_amount'] ?? 0
        ]);

        // AUTOALLORT: Auto-allocate to departments based on conversion rules
        $stmt_product->execute([$item['product_id']]);
        $product = $stmt_product->fetch();

        if ($product) {
            $stmt_conversion->execute([$product['item_code']]);
            $conversions = $stmt_conversion->fetchAll();

            $total_required_pieces = (int)$item['pieces']; // Simplification: using pieces for conversion calculation

            foreach ($conversions as $conv) {
                $qty_required = ceil($total_required_pieces * $conv['exact_multiplier_qty']);
                $dept_name = ucfirst(strtolower($conv['process_type'])) . ' Department';
                
                $stmt_queue->execute([
                    $po_id,
                    $dept_name,
                    $product['item_code'], // parent item code
                    $conv['component_name'], // required component
                    $qty_required
                ]);
            }
        }
    }

    $pdo->commit();
    
    // Notify WhatsApp Node Server
    $action_text = isset($is_update) && $is_update ? "Updated" : "Created";
    $wa_message = "🛒 *Purchase Order {$action_text}*\nPO ID: #{$po_id}\nTotal Boxes: {$total_boxes} | Total Pieces: {$total_pieces}\n";
    if ($is_po_urgent) {
        $wa_message .= "🚨 *URGENT ORDER*\n";
    }
    
    $ch = curl_init('http://localhost:3001/send');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $wa_message]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // Timeout of 2 seconds so PHP doesn't hang if node server is down
    curl_setopt($ch, CURLOPT_TIMEOUT, 2); 
    curl_exec($ch);
    curl_close($ch);

    echo json_encode(['success' => true, 'message' => 'Purchase order saved successfully', 'po_id' => $po_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to save PO: ' . $e->getMessage()]);
}
