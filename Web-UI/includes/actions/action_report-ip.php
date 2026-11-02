<?php
// includes/actions/action_report-ip.php

header('Content-Type: application/json; charset=utf-8');

// 1. Enforce authorization (Fix against unauthenticated anonymous access)
require_once __DIR__ . '/../auth.php';

if (!is_admin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only admins can report IPs.',
        'type'    => 'error'
    ]);
    exit;
}

$config = parse_ini_file('/opt/Fail2Ban-Report/Settings/fail2ban-report.config');

$ips = $_POST['ip'] ?? null;
if (!$config['report'] || !$config['report_types'] || !$ips) {
    echo json_encode([
        'success' => false,
        'message' => 'Reporting not enabled or invalid IP(s).',
        'type'    => 'info',
    ]);
    exit;
}

if (!is_array($ips)) {
    $ips = [$ips]; // Convert single IP to array
}

$services = array_map('trim', explode(',', $config['report_types']));
$results = [];
$allMessages = [];
$overallSuccess = true;

// Define whitelist of allowed services to prevent path manipulation hazards
$allowed_services = ['abuseipdb', 'ipinfo'];

foreach ($ips as $ip) {
    $ip = trim($ip);
    
    // 2. Validate IP address format (Fix against malformed or malicious inputs)
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $overallSuccess = false;
        $allMessages[] = "$ip → [Error] Invalid IP address format.";
        $results[] = [
            'ip'      => $ip,
            'success' => false,
            'message' => 'Invalid IP address format.',
            'data'    => [],
        ];
        continue;
    }

    $reportResults = [];
    $messages = [];
    $ipSuccess = true;

    foreach ($services as $service) {
        // Strict whitelist enforcement check
        if (!in_array($service, $allowed_services, true)) {
            $ipSuccess = false;
            $reportResults[$service] = [
                'success' => false,
                'message' => "Service '$service' is not allowed.",
                'type' => 'error'
            ];
            $messages[] = "[$service] disallowed service";
            continue;
        }

        $script = __DIR__ . "/reports/$service.php";

        // Check API keys for services that require them
        if ($service === 'abuseipdb' && empty($config['abuseipdb_key'])) {
            $ipSuccess = false;
            $reportResults[$service] = [
                'success' => false,
                'message' => 'AbuseIPDB API key not set.',
                'type' => 'error'
            ];
            $messages[] = "[$service] API key missing";
            continue;
        }
        if ($service === 'ipinfo' && empty($config['ipinfo_key'])) {
            $ipSuccess = false;
            $reportResults[$service] = [
                'success' => false,
                'message' => 'IPInfo API key not set.',
                'type' => 'error'
            ];
            $messages[] = "[$service] API key missing";
            continue;
        }

        if (file_exists($script)) {
            $_POST['ip'] = $ip;

            ob_start();
            include $script;
            $response = ob_get_clean();

            // Pause 500ms to avoid hitting API rate limits
            usleep(500000);

            $decoded = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $ipSuccess = false;
                $reportResults[$service] = [
                    'success' => false,
                    'message' => "Invalid JSON from $service: " . json_last_error_msg(),
                    'raw_response' => $response
                ];
                $messages[] = "[$service] JSON error";
            } else {
                if (empty($decoded['success'])) {
                    $ipSuccess = false;
                }
                $reportResults[$service] = $decoded;
                $messages[] = "[$service] " . ($decoded['message'] ?? 'Reported.');
            }
        } else {
            $ipSuccess = false;
            $reportResults[$service] = [
                'success' => false,
                'message' => "$service report script not available",
            ];
            $messages[] = "[$service] not available";
        }
    }

    if (!$ipSuccess) {
        $overallSuccess = false;
    }

    $ipMessage = implode(' | ', $messages);
    $allMessages[] = "$ip → $ipMessage";

    $results[] = [
        'ip'      => $ip,
        'success' => $ipSuccess,
        'message' => $ipMessage,
        'data'    => $reportResults,
    ];
}

// Final overall message type forced to 'info' for better UI
$finalType = 'info';
$finalMessage = implode(' || ', $allMessages);

echo json_encode([
    'success' => $overallSuccess,
    'message' => $finalMessage,
    'type'    => $finalType,
    'results' => $results
], JSON_PRETTY_PRINT);
