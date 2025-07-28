<?php
// includes/block-ip.php

/**
 * Blocks an IP address using iptables and logs the action into blocklist.json
 *
 * @param string $ip        The IP address to block.
 * @param string $jail      The fail2ban jail or context (optional).
 * @param string $source    Who triggered the block (e.g. 'manual', 'report', etc.)
 * @return array            Result array with 'success' and 'message'.
 */
function blockIp($ip, $jail = 'unknown', $source = 'manual') {
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return [
            'success' => false,
            'message' => "Invalid IP address format: $ip"
        ];
    }

    // Check if IP is already blocked (optional enhancement)
    // system("iptables -C INPUT -s $ip -j DROP 2>/dev/null", $alreadyBlocked);
    // if ($alreadyBlocked === 0) {
    //     return [
    //         'success' => true,
    //         'message' => "IP $ip is already blocked."
    //     ];
    // }

    // Run iptables command
    // Secure IP as Shell-Argument
    $ipEscaped = escapeshellarg($ip);

    // Path to IPTABLES (could be different on some systems)
    $cmd = "/sbin/iptables -A INPUT -s $ipEscaped -j DROP";

    $output = [];
    $exitCode = 0;

    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0) {
        return [
            'success' => false,
            'message' => "Failed to block IP $ip via iptables."
        ];
    }

    // Prepare JSON archive entry
    $entry = [
        'ip' => $ip,
        'jail' => $jail,
        'source' => $source,
        'timestamp' => date('c') // ISO 8601 format
    ];

    $jsonFile = __DIR__ . '/archive/blocklist.json';
    $data = [];

    if (file_exists($jsonFile)) {
        $existing = file_get_contents($jsonFile);
        $data = json_decode($existing, true);
        if (!is_array($data)) {
            $data = []; // fallback on corruption
        }
    }

    // Avoid duplicates
    foreach ($data as $item) {
        if ($item['ip'] === $ip) {
            return [
                'success' => true,
                'message' => "IP $ip was already listed in blocklist.json."
            ];
        }
    }

    $data[] = $entry;
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return [
        'success' => true,
        'message' => "IP $ip was successfully blocked and logged."
    ];
}
