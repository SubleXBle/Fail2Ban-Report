$config = parse_ini_file('/opt/Fail2Ban-Report/fail2ban-report.config');
$apiKey = trim($config['abuseipdb_key'] ?? '');

if (!$apiKey) {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB key not set.',
        'type' => 'error'
    ]);
    return;
}

$ipToCheck = $ip;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.abuseipdb.com/api/v2/check?ipAddress=$ipToCheck",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Key: $apiKey",
        "Accept: application/json"
    ]
]);

$response = curl_exec($curl);
curl_close($curl);

if ($response) {
    $json = json_decode($response, true);
    $count = $json['data']['totalReports'] ?? 0;

    $msg = "AbuseIPDB: $ipToCheck wurde $count mal gefunden";

    echo json_encode([
        'success' => true,
        'message' => $msg,
        'type' => ($count >= 10) ? 'error' : (($count > 0) ? 'info' : 'success') // rot, blau oder grün
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'AbuseIPDB request failed.',
        'type' => 'error'
    ]);
}
