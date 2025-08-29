<?php
header('Content-Type: application/json');

require_once __DIR__ . "/paths.php";
$archiveDirectory = $blocklistDir = $PATHS["fail2ban"];

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
        'aggregated' => [],
        'error' => 'No log files found.'
    ]);
    exit;
}

rsort($files); // newest first

// Today = newest File
$todayFile = $files[0];

// Aggregationtarget: [‘yesterday’ => 1, ‘last_7_days’ => 7, ‘last_30_days’ => 30]
$aggregationRanges = [
    'yesterday' => 1,
    'last_7_days' => 7,
    'last_30_days' => 30,
];

// process entrys
function processEntries($entries): array {
    $banTotal = 0;
    $unbanTotal = 0;
    $banIPs = [];
    $unbanIPs = [];

    foreach ($entries as $entry) {
        if (!isset($entry['action'], $entry['ip'])) continue;

        if ($entry['action'] === 'Ban') {
            $banTotal++;
            $banIPs[$entry['ip']] = true;
        } elseif ($entry['action'] === 'Unban') {
            $unbanTotal++;
            $unbanIPs[$entry['ip']] = true;
        }
    }

    return [
        'ban_count' => $banTotal,
        'ban_unique_ips' => count($banIPs),
        'unban_count' => $unbanTotal,
        'unban_unique_ips' => count($unbanIPs),
        'total_events' => $banTotal + $unbanTotal,
        'total_unique_ips' => count(array_unique(array_merge(array_keys($banIPs), array_keys($unbanIPs))))
    ];
}

// count bans per jail
function countBansPerJail(array $entries): array {
    $bansPerJail = [];

    foreach ($entries as $entry) {
        if (!isset($entry['action'], $entry['jail'])) continue;

        if ($entry['action'] === 'Ban') {
            $jail = $entry['jail'];
            if (!isset($bansPerJail[$jail])) {
                $bansPerJail[$jail] = 0;
            }
            $bansPerJail[$jail]++;
        }
    }

    return $bansPerJail;
}

// first todays file
$todayPath = $archiveDirectory . '/' . $todayFile;
$todayEntries = json_decode(file_get_contents($todayPath), true);
$todayStats = processEntries($todayEntries);

// Bans per Jail (only from today)
$banCountPerJail = countBansPerJail($todayEntries);

// count
$aggregatedStats = [];
foreach ($aggregationRanges as $label => $count) {
    $aggregatedEntries = [];

    // Skip n files if not enough are available
    for ($i = 1; $i <= $count && isset($files[$i]); $i++) {
        $filePath = $archiveDirectory . '/' . $files[$i];
        $content = json_decode(file_get_contents($filePath), true);
        if (is_array($content)) {
            $aggregatedEntries = array_merge($aggregatedEntries, $content);
        }
    }

    $aggregatedStats[$label] = processEntries($aggregatedEntries);
}

// Final JSON result with additional field ban_count_per_jail
echo json_encode(array_merge($todayStats, [
    'aggregated' => $aggregatedStats,
    'ban_count_per_jail' => $banCountPerJail,
]));
