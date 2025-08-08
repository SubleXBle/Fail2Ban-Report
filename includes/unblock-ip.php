<?php
// includes/unblock-ip.php

/**
 * Deactivates an IP address in the jail-specific blocklist (e.g. sshd.blocklist.json).
 *
 * @param string $ip    The IP address to unblock.
 * @param string $jail  The jail name (e.g. 'sshd').
 * @return array        Result array with 'success' and 'message'.
 */
function unblockIp($ip, $jail) {
    // Validate IP address
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return [
            'success' => false,
            'message' => "Invalid IP address format: $ip"
        ];
    }

    // Validate jail name (simple check)
    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $jail)) {
        return [
            'success' => false,
            'message' => "Invalid jail name: $jail"
        ];
    }

    // Construct jail-specific blocklist path
    $blocklistFile = __DIR__ . '/../archive/' . $jail . '.blocklist.json';

    if (!file_exists($blocklistFile)) {
        return [
            'success' => false,
            'message' => "Blocklist file for jail '$jail' not found."
        ];
    }

    // Load existing data
    $raw = file_get_contents($blocklistFile);
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return [
            'success' => false,
            'message' => "Corrupted or unreadable blocklist for jail '$jail'."
        ];
    }

    $found = false;

    foreach ($data as &$item) {
        if ($item['ip'] === $ip) {
            if (isset($item['active']) && $item['active'] === false) {
                return [
                    'success' => true,
                    'message' => "IP $ip was already inactive in $jail."
                ];
            }

            $item['active'] = false;
            $item['pending'] = true;
            $item['lastModified'] = date('c');
            $found = true;
            break;
        }
    }
    unset($item);

    if (!$found) {
        return [
            'success' => false,
            'message' => "IP $ip not found in $jail blocklist."
        ];
    }

    // Write updated blocklist
    if (file_put_contents($blocklistFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        return [
            'success' => false,
            'message' => "Failed to write to $jail blocklist."
        ];
    }

    return [
        'success' => true,
        'message' => "IP $ip successfully marked as inactive in $jail blocklist."
    ];
}
