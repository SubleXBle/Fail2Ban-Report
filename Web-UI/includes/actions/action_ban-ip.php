<?php
// includes/actions/action_ban-ip.php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../block-ip.php';

// Check if IP(s) were provided
if (!isset($_POST['ip'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing IP address(es)',
        'type' => 'error'
    ]);
    exit;
}

// Normalize input
$ips = $_POST['ip'];
if (!is_array($ips)) {
    $ips = [$ips]; // Single IP fallback
}

$jail = $_POST['jail'] ?? 'unknown';
$source = $_POST['source'] ?? 'action_ban-ip';

$results = [];

foreach ($ips as $ip) {
    $ip = trim($ip);
    $result = blockIp($ip, $jail, $source);

    // Ensure each result has a type
    $type = $result['type'] ?? ($result['success'] ? 'success' : 'error');

    $results[] = [
        'ip' => $ip,
        'success' => $result['success'],
        'message' => $result['message'],
        'type' => $type
    ];
}

// HTTP-Status abhängig vom Ergebnis
$hasError = array_filter($results, fn($r) => $r['type'] === 'error');
http_response_code(count($hasError) > 0 ? 207 : 200);

// Dominantesten Typ ermitteln (error > info > success)
$priority = ['error' => 3, 'info' => 2, 'success' => 1];
$finalType = 'success';
foreach ($results as $r) {
    if (isset($r['type']) && $priority[$r['type']] > $priority[$finalType]) {
        $finalType = $r['type'];
    }
}

// Nachrichten zusammenbauen
$messages = array_map(fn($r) => "[{$r['ip']}] {$r['message']}", $results);
$combinedMessage = implode(" | ", $messages);

// Finales JSON
echo json_encode([
    'results' => $results,
    'message' => $combinedMessage,
    'type' => $finalType
], JSON_PRETTY_PRINT);
