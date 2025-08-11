<?php
// Set correct path to your blocklist directory
$blocklistDir = '/var/www/vhosts/suble.org/xbkupx/Fail2Ban-Report/archive/';
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
    // Count pending entries (pending === true)
    if (isset($entry['pending']) && $entry['pending'] === true) {
        $pending++;
    }

    // Count active entries only if not pending
    if (
        isset($entry['active']) && $entry['active'] === true &&
        (!isset($entry['pending']) || $entry['pending'] === false)
    ) {
        $active++;
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
