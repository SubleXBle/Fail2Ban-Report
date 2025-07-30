<?php
// includes/actions/action_unban-ip.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../unblock-ip.php';

if (!isset($_POST['ip'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing IP address']);
    exit;
}

$ip = trim($_POST['ip']);

$result = unblockIp($ip);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => $result['message']]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
