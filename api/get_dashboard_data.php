<?php
// api/get_dashboard_data.php
require_once '../includes/auth.php';
requireLogin();

$user_role = $_SESSION['role_name'];
$is_admin = ($user_role === 'Super Admin');

try {
    $data = [];

    // 1. Get recent POs for order tracking
    $po_stmt = $pdo->query('
        SELECT p.*, c.client_name 
        FROM purchase_orders p 
        JOIN clients c ON p.client_id = c.id 
        ORDER BY p.order_date DESC 
        LIMIT 10
    ');
    $data['recent_orders'] = $po_stmt->fetchAll();

    // 2. Combined Totals Master (Total active products needed)
    $totals_stmt = $pdo->query('
        SELECT pr.item_code, pr.current_name, SUM(pi.boxes) as total_boxes, SUM(pi.pieces) as total_pieces
        FROM po_items pi
        JOIN products pr ON pi.product_id = pr.id
        JOIN purchase_orders p ON pi.po_id = p.id
        WHERE p.status = "Pending"
        GROUP BY pr.item_code, pr.current_name
    ');
    $data['combined_totals'] = $totals_stmt->fetchAll();

    // 3. Raw Material Pendings (Filtered by role if not admin)
    $queue_sql = '
        SELECT dq.department_name, dq.item_code, dq.item_name, SUM(dq.quantity_required) as total_required
        FROM department_queues dq
        JOIN purchase_orders p ON dq.po_id = p.id
        WHERE dq.status = "Pending"
    ';
    $queue_params = [];

    if (!$is_admin) {
        $queue_sql .= ' AND dq.department_name = ?';
        $queue_params[] = $user_role;
    }

    $queue_sql .= ' GROUP BY dq.department_name, dq.item_code, dq.item_name';
    
    $queue_stmt = $pdo->prepare($queue_sql);
    $queue_stmt->execute($queue_params);
    $data['raw_material_pendings'] = $queue_stmt->fetchAll();

    // 4. Clients list for dropdowns
    $clients_stmt = $pdo->query('SELECT id, client_name FROM clients ORDER BY client_name');
    $data['clients'] = $clients_stmt->fetchAll();

    // 5. Products list for dropdowns
    $products_stmt = $pdo->query('SELECT id, item_code, current_name FROM products ORDER BY current_name');
    $data['products'] = $products_stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
