<?php
header('Content-Type: application/json');

require_once __DIR__ . "/paths.php";

// read Config
$configPath = '/opt/Fail2Ban-Report/fail2ban-report.config';
if (!file_exists($configPath)) {
    echo json_encode(['status' => 'disabled', 'reason' => 'No config found']);
    exit;
}

$config = parse_ini_file($configPath, true);

// Warnings only active, when in Config enabled = true steht (bool valid Check)
if (empty($config['Warnings']['enabled']) || !filter_var($config['Warnings']['enabled'], FILTER_VALIDATE_BOOLEAN)) {
    echo json_encode(['status' => 'disabled', 'reason' => 'Warnings not enabled']);
    exit;
}

// Fallback 20:50 for warn and crit - warnings in header
$thresholdRaw = $config['Warnings']['threshold'] ?? '20:50';
[$warnThreshold, $criticalThreshold] = array_map('intval', explode(':', $thresholdRaw));

// find newest log in archive
$archiveDir = $PATHS["fail2ban"];
$files = array_filter(scandir($archiveDir), fn($f) => preg_match('/^fail2ban-events-\d{8}\.json$/', $f));
rsort($files); // neueste zuerst
$todayFile = $files[0] ?? null;

if (!$todayFile || !file_exists($archiveDir . $todayFile)) {
    echo json_encode(['status' => 'error', 'reason' => 'No log data found']);
    exit;
}

// load JSON Data
$entries = json_decode(file_get_contents($archiveDir . $todayFile), true);
if (!is_array($entries)) {
    echo json_encode(['status' => 'error', 'reason' => 'Invalid JSON log']);
    exit;
}

// Group Events - Jail and Minute (Minute als 'Y-m-d H:i')
$jailMinuteEvents = [];

foreach ($entries as $entry) {
    if (!isset($entry['action'], $entry['ip'], $entry['jail'], $entry['timestamp'])) continue;
    if ($entry['action'] !== 'Ban') continue;

    $jail = $entry['jail'];
    $ip = $entry['ip'];
    $minute = date('Y-m-d H:i', strtotime(str_replace(',', '.', $entry['timestamp'])));

    $jailMinuteEvents[$jail][$minute][] = $ip;
}

// Analyze: count Events and unique IPs per Jail per Minute, classify as Warn or Crit
$warnings = [];
$criticals = [];

foreach ($jailMinuteEvents as $jail => $minutes) {
    foreach ($minutes as $minute => $ips) {
        $eventCount = count($ips);
        $uniqueIPCount = count(array_unique($ips));

        if ($eventCount >= $criticalThreshold) {
            // Critical
            if (!isset($criticals[$jail])) {
                $criticals[$jail] = ['events' => 0, 'unique_ips' => 0];
            }
            $criticals[$jail]['events'] += $eventCount;
            $criticals[$jail]['unique_ips'] += $uniqueIPCount;
        } elseif ($eventCount >= $warnThreshold) {
            // Warning (only when not crit)
            if (!isset($warnings[$jail])) {
                $warnings[$jail] = ['events' => 0, 'unique_ips' => 0];
            }
            $warnings[$jail]['events'] += $eventCount;
            $warnings[$jail]['unique_ips'] += $uniqueIPCount;
        }
    }
}

// forge response
$response = [
    'status' => 'ok',
    'warning' => [
        'total_events' => array_sum(array_column($warnings, 'events')),
        'total_unique_ips' => array_sum(array_column($warnings, 'unique_ips')),
        'total_jails' => count($warnings),
        'jails' => $warnings,
        'jail_names' => array_keys($warnings)
    ],
    'critical' => [
        'total_events' => array_sum(array_column($criticals, 'events')),
        'total_unique_ips' => array_sum(array_column($criticals, 'unique_ips')),
        'total_jails' => count($criticals),
        'jails' => $criticals,
        'jail_names' => array_keys($criticals)
    ],
    'enabled' => true
];


echo json_encode($response);
