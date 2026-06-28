<?php
header('Content-Type: application/json');

$qr_file = '../qr_code.txt';

if (file_exists($qr_file)) {
    $qr_data = trim(file_get_contents($qr_file));
    if ($qr_data === 'CONNECTED') {
        echo json_encode(['success' => true, 'status' => 'CONNECTED']);
    } else if (!empty($qr_data)) {
        echo json_encode(['success' => true, 'status' => 'QR', 'qr' => $qr_data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Waiting for QR generation...']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'WhatsApp reporter is not running. Please start it using node whatsapp_reporter.js']);
}
