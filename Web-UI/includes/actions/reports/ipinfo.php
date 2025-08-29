<?php
// includes/actions/reports/ipinfo.php

require_once __DIR__ . '/../paths.php';

// Config laden
$config = parse_ini_file($PATHS['config'] . "fail2ban-report.config", true);
$apiKey = trim($config['IP-Info API Key']['ipinfo_key'] ?? '');

// IP aus POST
$ipToCheck = $_POST['ip'] ?? null;

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'IPInfo API key not set.',
        'type' => 'error'
    ]);
    return;
}

if (!$ipToCheck) {
    echo json_encode([
        'success' => false,
        'message' => 'No IP specified for IPInfo check.',
        'type' => 'error'
    ]);
    return;
}

// API Call
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://ipinfo.io/{$ipToCheck}/json?token={$apiKey}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ["Accept: application/json"],
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

if (!$response) {
    echo json_encode([
        'success' => false,
        'message' => $curlError ?: 'IPInfo request failed.',
        'type' => 'error'
    ]);
    return;
}

$json = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'IPInfo: Invalid JSON response.',
        'type' => 'error',
        'raw_response' => $response
    ]);
    return;
}

$msg = "IPInfo: {$json['ip'] ?? 'unknown'} - Hostname: {$json['hostname'] ?? 'N/A'}, Location: {$json['city'] ?? 'N/A'}, {$json['region'] ?? 'N/A'}, {$json['country'] ?? 'N/A'}, Org: {$json['org'] ?? 'N/A'}";

echo json_encode([
    'success' => true,
    'message' => $msg,
    'type' => 'info',
    'data' => $json
]);
