<?php
header('Content-Type: application/json');

// Base directory for JSON files
$archiveDirectory = dirname(__DIR__) . '/archive/';

// Determine the latest JSON file in the archive directory
$files = array_filter(scandir($archiveDirectory), function($file) {
    return preg_match('/^fail2ban-events-\d{8}\.json$/', $file);
});

if (!$files) {
    echo json_encode([
        'ban_count' => 0,
        'ban_unique_ips' => 0,
        'unban_count' => 0,
        'unban_unique_ips' => 0,
        'total_events' => 0,
        'total_unique_ips' => 0,
        'error' => 'No log files found.'
    ]);
    exit;
}

// Sort descending by filename (newest first)
rsort($files);

$logFilename = $files[0];
$logFilePath = $archiveDirectory . '/' . $logFilename;

if (!is_readable($logFilePath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found or not readable.']);
    exit;
}

$jsonContent = file_get_contents($logFilePath);
if ($jsonContent === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Error reading the file.']);
    exit;
}

$logEntries = json_decode($jsonContent, true);
if (!is_array($logEntries)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON format.']);
    exit;
}

// Initialize counters and sets
$banTotal = 0;
$unbanTotal = 0;
$banIPsSet = [];
$unbanIPsSet = [];

foreach ($logEntries as $entry) {
    if (!isset($entry['action'], $entry['ip'])) continue;

    if ($entry['action'] === 'Ban') {
        $banTotal++;
        $banIPsSet[$entry['ip']] = true;
    } elseif ($entry['action'] === 'Unban') {
        $unbanTotal++;
        $unbanIPsSet[$entry['ip']] = true;
    }
}

$totalEvents = $banTotal + $unbanTotal;
$totalUniqueIPs = count(array_unique(array_merge(array_keys($banIPsSet), array_keys($unbanIPsSet))));

echo json_encode([
    'ban_count' => $banTotal,
    'ban_unique_ips' => count($banIPsSet),
    'unban_count' => $unbanTotal,
    'unban_unique_ips' => count($unbanIPsSet),
    'total_events' => $totalEvents,
    'total_unique_ips' => $totalUniqueIPs,
]);
