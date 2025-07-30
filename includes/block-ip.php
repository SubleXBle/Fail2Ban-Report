<?php
// includes/block-ip.php

/**
 * Blocks an IP address by adding it to blocklist.json (no iptables calls).
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

    $jsonFile = dirname(__DIR__, 1) . '/archive/blocklist.json';
    $data = [];

    // Read existing blocklist
    if (file_exists($jsonFile)) {
        $existing = file_get_contents($jsonFile);
        $data = json_decode($existing, true);
        if (!is_array($data)) {
            $data = []; // fallback on corruption
        }
    }

    // Check for existing IP, reactivate if found but inactive
    foreach ($data as &$item) {
        if ($item['ip'] === $ip) {
            if (!isset($item['active']) || $item['active'] === false) {
                $item['active'] = true;
                $item['lastModified'] = date('c');
                // Save updated blocklist
                if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                    return [
                        'success' => false,
                        'message' => "Failed to write to blocklist.json."
                    ];
                }
                return [
                    'success' => true,
                    'message' => "IP $ip was reactivated in blocklist.json."
                ];
            }
            return [
                'success' => true,
                'message' => "IP $ip is already active in blocklist.json."
            ];
        }
    }
    unset($item);

    // New block entry with optional fields for future use
    $entry = [
        'ip' => $ip,
        'jail' => $jail,
        'source' => $source,
        'timestamp' => date('c'), // ISO 8601 format
        'expires' => null,
        'reason' => '',
        'active' => true,
        'lastModified' => date('c'),
        'tags' => []
    ];

    $data[] = $entry;

    // Save updated blocklist
    if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        return [
            'success' => false,
            'message' => "Failed to write to blocklist.json."
        ];
    }

    return [
        'success' => true,
        'message' => "IP $ip was successfully added to blocklist.json."
    ];
}
