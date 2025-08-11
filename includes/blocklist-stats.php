<?php
// Set correct path to your blocklist directory
$blocklistDir = dirname(__DIR__) . '/archive/';
$stats = [];

foreach (glob($blocklistDir . '*.blocklist.json') as $filepath) {
    $filename = basename($filepath);

    // Extract jail name (remove .blocklist.json)
    $jail = preg_replace('/\.blocklist\.json$/', '', $filename);
    if (!$jail) continue;

    // Read JSON
    $json = file_get_contents($filepath);
    if (!$json) continue;

    $entries = json_decode($json, true);
    if (!is_array($entries)) continue;

    // Initialize counters
    $active = 0;
    $pending = 0;

    foreach ($entries as $entry) {
        if (!isset($entry['active'])) continue;
        if ($entry['active'] === true) {
            $active++;
        } else {
            $pending++;
        }
    }

    // Store result
    $stats[$jail] = [
        'active' => $active,
        'pending' => $pending
    ];
}

// Output JSON
header('Content-Type: application/json');
echo json_encode($stats);
