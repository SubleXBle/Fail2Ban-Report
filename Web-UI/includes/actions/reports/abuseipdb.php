<?php
// includes/actions/reports/abuseipdb.php

require_once __DIR__ . '/../paths.php';

// Config laden
$config = parse_ini_file($PATHS['config'] . "fail2ban-report.config", true);
$apiKey = trim($config['AbuseIPDB API Key']['abuseipdb_key'] ?? '');

// IP aus POST
$ipToCheck = $_POST['ip'] ?? null;

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB API key not set.',
        'type' => 'error'
    ]);
    return;
}

if (!$ipToCheck) {
    echo json_encode([
        'success' => false,
        'message' => 'No IP specified for AbuseIPDB check.',
        'type' => 'error'
    ]);
    return;
}

// API Call
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.abuseipdb.com/api/v2/check?ipAddress=$ipToCheck",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Key: $apiKey",
        "Accept: application/json"
    ],
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

if (!$response) {
    echo json_encode([
        'success' => false,
        'message' => $curlError ?: 'AbuseIPDB request failed.',
        'type' => 'error'
    ]);
    return;
}

$json = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB: Invalid JSON response.',
        'type' => 'error',
        'raw_response' => $response
    ]);
    return;
}

$count = $json['data']['totalReports'] ?? null;
if ($count === null) {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB: Unexpected API response structure.',
        'type' => 'error',
        'raw_response' => $response
    ]);
    return;
}

$msg = "AbuseIPDB: $ipToCheck was reported $count time(s).";

echo json_encode([
    'success' => true,
    'message' => $msg,
    'type' => ($count >= 10) ? 'error' : (($count > 0) ? 'info' : 'success'),
    'data' => $json
]);
