<?php
// includes/unblock-ip.php

/**
 * Deactivates an IP address in blocklist.json (no iptables calls).
 *
 * @param string $ip        The IP address to unblock.
 * @return array            Result array with 'success' and 'message'.
 */
function unblockIp($ip) {
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return [
            'success' => false,
            'message' => "Invalid IP address format: $ip"
        ];
    }

    $jsonFile = 'archive/blocklist.json';
    $data = [];

    // Read existing blocklist
    if (file_exists($jsonFile)) {
        $existing = file_get_contents($jsonFile);
        $data = json_decode($existing, true);
        if (!is_array($data)) {
            return [
                'success' => false,
                'message' => "Corrupted or unreadable blocklist.json."
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => "Blocklist file not found."
        ];
    }

    $found = false;

    foreach ($data as &$item) {
        if ($item['ip'] === $ip) {
            if (isset($item['active']) && $item['active'] === false) {
                return [
                    'success' => true,
                    'message' => "IP $ip was already inactive."
                ];
            }

            $item['active'] = false;
            $item['lastModified'] = date('c');
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        return [
            'success' => false,
            'message' => "IP $ip not found in blocklist.json."
        ];
    }

    // Save updated blocklist
    if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        return [
            'success' => false,
            'message' => "Failed to write to blocklist.json."
        ];
    }

    return [
        'success' => true,
        'message' => "IP $ip was successfully marked as inactive in blocklist.json."
    ];
}
