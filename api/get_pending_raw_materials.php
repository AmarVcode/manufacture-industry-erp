<?php
require '../includes/db.php';
header('Content-Type: application/json');

try {
    // 1. Get Pending Finished Goods (Total & Client-wise)
    // We assume POs with status 'Pending' are what we need to look at.
    $stmt_fg = $pdo->query("
        SELECT 
            p.item_code, 
            p.current_name, 
            c.client_name,
            SUM(poi.pieces) as total_pieces,
            poi.lazer_print,
            SUM(poi.lazer_print_amount) as total_lazer_amount
        FROM po_items poi
        JOIN products p ON poi.product_id = p.id
        JOIN purchase_orders po ON poi.po_id = po.id
        JOIN clients c ON po.client_id = c.id
        WHERE po.status = 'Pending'
        GROUP BY p.item_code, p.current_name, c.client_name, poi.lazer_print
    ");
    $fg_rows = $stmt_fg->fetchAll();

    $total_fg = [];
    $client_fg = [];

    foreach ($fg_rows as $row) {
        $code = $row['item_code'];
        $name = $row['current_name'];
        $client = $row['client_name'];
        
        // Aggregate Total FG
        $key = $code . '_' . $row['lazer_print'];
        if (!isset($total_fg[$key])) {
            $total_fg[$key] = [
                'code' => $code,
                'name' => $name,
                'amount' => 0,
                'lazer_print' => $row['lazer_print'],
                'lazer_amount' => 0
            ];
        }
        $total_fg[$key]['amount'] += (int)$row['total_pieces'];
        $total_fg[$key]['lazer_amount'] += (int)$row['total_lazer_amount'];

        // Aggregate Client-wise FG
        if (!isset($client_fg[$client])) {
            $client_fg[$client] = [];
        }
        if (!isset($client_fg[$client][$key])) {
            $client_fg[$client][$key] = [
                'code' => $code,
                'name' => $name,
                'amount' => 0
            ];
        }
        $client_fg[$client][$key]['amount'] += (int)$row['total_pieces'];
    }

    // 2. Get Pending Raw Materials (Total & Client-wise)
    // We'll calculate this directly from the pending FG amounts and conversions.
    $stmt_conv = $pdo->query("SELECT * FROM raw_material_conversion");
    $conversions = $stmt_conv->fetchAll();
    
    $conv_map = [];
    foreach ($conversions as $conv) {
        $conv_map[$conv['parent_product_code']][] = $conv;
    }

    $total_rm = [];
    $client_rm = [];

    // Build RM for Total
    foreach ($total_fg as $fg) {
        $code = $fg['code'];
        $qty = $fg['amount'];
        $name = $fg['name'];

        if (!isset($total_rm[$name])) {
            $total_rm[$name] = ['fg_name' => $name, 'components' => []];
        }
        
        if (isset($conv_map[$code])) {
            foreach ($conv_map[$code] as $c) {
                $total_rm[$name]['components'][] = [
                    'process' => $c['process_type'],
                    'component_name' => $c['component_name'],
                    'amount' => ceil($qty * $c['exact_multiplier_qty'])
                ];
            }
        }
    }

    // Build RM for Client-wise
    foreach ($client_fg as $client => $fgs) {
        if (!isset($client_rm[$client])) {
            $client_rm[$client] = [];
        }
        foreach ($fgs as $fg) {
            $code = $fg['code'];
            $qty = $fg['amount'];
            $name = $fg['name'];

            if (!isset($client_rm[$client][$name])) {
                $client_rm[$client][$name] = ['fg_name' => $name, 'components' => []];
            }

            if (isset($conv_map[$code])) {
                foreach ($conv_map[$code] as $c) {
                    $client_rm[$client][$name]['components'][] = [
                        'process' => $c['process_type'],
                        'component_name' => $c['component_name'],
                        'amount' => ceil($qty * $c['exact_multiplier_qty'])
                    ];
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'total_pending_fg' => array_values($total_fg),
        'client_pending_fg' => array_map('array_values', $client_fg),
        'total_pending_rm' => array_values($total_rm),
        'client_pending_rm' => array_map('array_values', $client_rm)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
