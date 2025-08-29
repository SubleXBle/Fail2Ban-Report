<?php
// includes/actions/action_unban-ip.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../unblock-ip.php';

// Validate input
if (!isset($_POST['ip']) || !isset($_POST['jail'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing IP address or jail']);
    exit;
}

$ip = trim($_POST['ip']);
$jail = trim($_POST['jail']);

// Call the unblock function with jail context
$result = unblockIp($ip, $jail);

if ($result['success']) {
    echo json_encode(['success' => true, 'message' => $result['message']]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message']]);
}
