<?php
header('Content-Type: application/json');

// Config-Datei einlesen
$configPath = '/opt/Fail2Ban-Report/fail2ban-report.config';
if (!file_exists($configPath)) {
    echo json_encode(['status' => 'disabled', 'reason' => 'No config found']);
    exit;
}

$config = parse_ini_file($configPath, true);

// Warnings nur aktiv, wenn in der Config enabled auf true steht (bool valider Check)
if (empty($config['Warnings']['enabled']) || !filter_var($config['Warnings']['enabled'], FILTER_VALIDATE_BOOLEAN)) {
    echo json_encode(['status' => 'disabled', 'reason' => 'Warnings not enabled']);
    exit;
}

// Schwellenwerte auslesen, Fallback 20:50 falls nicht gesetzt
$thresholdRaw = $config['Warnings']['threshold'] ?? '20:50';
[$warnThreshold, $criticalThreshold] = array_map('intval', explode(':', $thresholdRaw));

// Archive-Verzeichnis und neueste Log-Datei finden
$archiveDir = dirname(__DIR__) . '/archive/';
$files = array_filter(scandir($archiveDir), fn($f) => preg_match('/^fail2ban-events-\d{8}\.json$/', $f));
rsort($files); // neueste zuerst
$todayFile = $files[0] ?? null;

if (!$todayFile || !file_exists($archiveDir . $todayFile)) {
    echo json_encode(['status' => 'error', 'reason' => 'No log data found']);
    exit;
}

// JSON-Einträge laden
$entries = json_decode(file_get_contents($archiveDir . $todayFile), true);
if (!is_array($entries)) {
    echo json_encode(['status' => 'error', 'reason' => 'Invalid JSON log']);
    exit;
}

// Events gruppieren nach Jail und Minute (Minute als 'Y-m-d H:i')
$jailMinuteEvents = [];

foreach ($entries as $entry) {
    if (!isset($entry['action'], $entry['ip'], $entry['jail'], $entry['timestamp'])) continue;
    if ($entry['action'] !== 'Ban') continue;

    $jail = $entry['jail'];
    $ip = $entry['ip'];
    $minute = date('Y-m-d H:i', strtotime(str_replace(',', '.', $entry['timestamp'])));

    $jailMinuteEvents[$jail][$minute][] = $ip;
}

// Analyse: Zähle Events und einzigartige IPs pro Jail/Minute, klassifiziere als Warn oder Crit
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
            // Warning (nur, wenn nicht schon als critical gewertet)
            if (!isset($warnings[$jail])) {
                $warnings[$jail] = ['events' => 0, 'unique_ips' => 0];
            }
            $warnings[$jail]['events'] += $eventCount;
            $warnings[$jail]['unique_ips'] += $uniqueIPCount;
        }
    }
}

// Antwort zusammensetzen
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
