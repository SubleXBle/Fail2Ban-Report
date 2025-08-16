<?php
// abuseipdb.php

$config = parse_ini_file('/opt/Fail2Ban-Report/fail2ban-report.config');
$apiKey = trim($config['abuseipdb_key'] ?? '');

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB API key not set.',
        'type' => 'error'
    ]);
    return;
}

$ipToCheck = $ip ?? null;

if (!$ipToCheck) {
    echo json_encode([
        'success' => false,
        'message' => 'No IP specified for AbuseIPDB check.',
        'type' => 'error'
    ]);
    return;
}

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
curl_close($curl);

if ($response) {
    $json = json_decode($response, true);
    $count = $json['data']['totalReports'] ?? null;

    if ($count === null) {
        echo json_encode([
            'success' => false,
            'message' => 'AbuseIPDB: Unexpected API response.',
            'type' => 'error'
        ]);
        return;
    }

    $msg = "AbuseIPDB: $ipToCheck was reported $count time(s).";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'type' => ($count >= 10) ? 'error' : (($count > 0) ? 'info' : 'success')
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB request failed.',
        'type' => 'error'
    ]);
}
