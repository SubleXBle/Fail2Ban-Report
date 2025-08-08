<?php
// ipinfo.php

$config = parse_ini_file('/opt/Fail2Ban-Report/fail2ban-report.config');
$apiKey = trim($config['ipinfo_key'] ?? '');

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'IPInfo API key not set.',
        'type' => 'error'
    ]);
    return;
}

$ipToCheck = $ip ?? null;

if (!$ipToCheck) {
    echo json_encode([
        'success' => false,
        'message' => 'No IP specified for IPInfo check.',
        'type' => 'error'
    ]);
    return;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://ipinfo.io/{$ipToCheck}/json?token={$apiKey}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json"
    ],
]);

$response = curl_exec($curl);
$curlError = curl_error($curl);
curl_close($curl);

if ($response) {
    $json = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode([
            'success' => false,
            'message' => 'IPInfo: Invalid JSON response.',
            'type' => 'error'
        ]);
        return;
    }

    // Example fields from IPInfo
    $ip = $json['ip'] ?? 'unknown';
    $hostname = $json['hostname'] ?? 'N/A';
    $city = $json['city'] ?? 'N/A';
    $region = $json['region'] ?? 'N/A';
    $country = $json['country'] ?? 'N/A';
    $org = $json['org'] ?? 'N/A';
    $loc = $json['loc'] ?? 'N/A';
    $postal = $json['postal'] ?? 'N/A';

    $msg = "IPInfo: $ip - Hostname: $hostname, Location: $city, $region, $country, Org: $org";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'data' => $json,
        'type' => 'info'
    ]);
} else {
    $errorMsg = $curlError ?: 'IPInfo request failed.';
    echo json_encode([
        'success' => false,
        'message' => $errorMsg,
        'type' => 'error'
    ]);
}
