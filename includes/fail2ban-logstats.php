<?php
header('Content-Type: application/json');

$archiveDirectory = dirname(__DIR__) . '/archive/';

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

// Heute = neueste Datei
$todayFile = $files[0];

// Aggregationsziel: [‘yesterday’ => 1, ‘last_7_days’ => 7, ‘last_30_days’ => 30]
$aggregationRanges = [
    'yesterday' => 1,
    'last_7_days' => 7,
    'last_30_days' => 30,
];

// Funktion zur Verarbeitung von Einträgen
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

// Zähle Bans pro Jail in einem Eintrags-Array
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

// Zuerst: heutige Datei verarbeiten
$todayPath = $archiveDirectory . '/' . $todayFile;
$todayEntries = json_decode(file_get_contents($todayPath), true);
$todayStats = processEntries($todayEntries);

// Zusätzliche Statistik: Bans pro Jail (nur heute)
$banCountPerJail = countBansPerJail($todayEntries);

// Dann aggregierte Werte berechnen
$aggregatedStats = [];
foreach ($aggregationRanges as $label => $count) {
    $aggregatedEntries = [];

    // n Dateien überspringen wenn nicht genug vorhanden
    for ($i = 1; $i <= $count && isset($files[$i]); $i++) {
        $filePath = $archiveDirectory . '/' . $files[$i];
        $content = json_decode(file_get_contents($filePath), true);
        if (is_array($content)) {
            $aggregatedEntries = array_merge($aggregatedEntries, $content);
        }
    }

    $aggregatedStats[$label] = processEntries($aggregatedEntries);
}

// Finales JSON-Resultat mit zusätzlichem Feld ban_count_per_jail
echo json_encode(array_merge($todayStats, [
    'aggregated' => $aggregatedStats,
    'ban_count_per_jail' => $banCountPerJail,
]));
