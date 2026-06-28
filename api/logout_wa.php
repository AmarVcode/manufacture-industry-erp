<?php
header('Content-Type: application/json');

$ch = curl_init('http://localhost:3001/logout');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
$res = curl_exec($ch);
curl_close($ch);

if (file_exists('../qr_code.txt')) {
    unlink('../qr_code.txt');
}

echo json_encode(['success' => true]);
