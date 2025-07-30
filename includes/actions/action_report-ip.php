<?php

$ip = $_POST['ip'] ?? null;
$config = parse_ini_file('/opt/Fail2Ban-Report/fail2ban-report.config');

if (!$config['report'] || !$config['report_types'] || !$ip) {
    echo json_encode([
        'success' => false,
        'message' => 'Reporting not enabled or invalid IP.',
        'type' => 'info',
    ]);
    exit;
}

$services = explode(',', $config['report_types']);
$results = [];

foreach ($services as $service) {
    $service = trim($service);
    $script = __DIR__ . "/reports/$service.php";

    if (file_exists($script)) {
        ob_start();
        include $script;
        $response = ob_get_clean();

        // Debug-Ausgabe ins Log
       // file_put_contents('/tmp/report_debug.log', "Service: $service\nResponse:\n$response\n\n", FILE_APPEND);

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $results[$service] = [
                'success' => false,
                'message' => "Invalid JSON response: " . json_last_error_msg(),
                'raw_response' => $response
            ];
        } else {
            $results[$service] = $decoded;
        }

    } else {
        $results[$service] = ['success' => false, 'message' => "$service not available"];
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Reports collected.',
    'data' => $results,
    'type' => 'info',
]);
