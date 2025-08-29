<?php
// Set correct path to your blocklist directory

require_once __DIR__ . "/paths.php";

$blocklistDir = $PATHS["blocklists"];

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

        // Count active entries with new rule:
        // 1) active === true and pending missing or false
        // 2) active === false and pending === true
        if (
            (isset($entry['active']) && $entry['active'] === true &&
             (!isset($entry['pending']) || $entry['pending'] === false))
            ||
            (isset($entry['active']) && $entry['active'] === false &&
             isset($entry['pending']) && $entry['pending'] === true)
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
