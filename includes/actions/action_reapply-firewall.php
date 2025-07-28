<?php
// includes/actions/action_reapply-firewall.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../block-ip.php';

$jsonFile = __DIR__ . '/../archive/blocklist.json';

if (!file_exists($jsonFile)) {
    echo json_encode(['success' => false, 'message' => 'Blocklist file not found']);
    exit;
}

$data = json_decode(file_get_contents($jsonFile), true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Blocklist data corrupted']);
    exit;
}

$results = [];
foreach ($data as $entry) {
    $ip = $entry['ip'] ?? '';
    $jail = $entry['jail'] ?? 'unknown';
    if (!$ip) continue;

    $result = blockIp($ip, $jail, 'reapply-firewall');
    $results[] = [
        'ip' => $ip,
        'success' => $result['success'],
        'message' => $result['message']
    ];
}

// Optional: Prüfen, ob alle erfolgreich waren
$allSuccess = array_reduce($results, fn($carry, $item) => $carry && $item['success'], true);

echo json_encode([
    'success' => $allSuccess,
    'message' => $allSuccess ? 'Alle IPs erfolgreich erneut blockiert.' : 'Es gab Fehler bei einigen IPs.',
    'details' => $results
]);
