<?php
// includes/unblock-ip.php

/**
 * Unblocks one or multiple IP addresses by marking them inactive
 * in their jail-specific blocklist JSON files.
 *
 * @param string|array $ips        IP address or array of IP addresses to unblock.
 * @param string $jail             Fail2Ban jail/context name (optional).
 * @return array                   Result array or array of results with 'success', 'message' and 'type'.
 */

require_once __DIR__ . "/paths.php";

function unblockIp($ips, $jail = 'unknown') {
    $results = [];

    if (!is_array($ips)) {
        $ips = [$ips];
    }

    foreach ($ips as $ip) {
        $ip = trim($ip);

        // Validate IP address format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "Invalid IP address: $ip",
                'type' => 'error'
            ];
            continue;
        }

        // Sanitize jail name
        $safeJail = strtolower(preg_replace('/[^a-z0-9_-]/', '', $jail));
        if ($safeJail === '') {
            $safeJail = 'unknown';
        }

        $jsonFile = $GLOBALS["PATHS"]["blocklists"] . $safeJail . ".blocklist.json";
        $lockFile = "/tmp/{$safeJail}.blocklist.lock";

        if (!file_exists($jsonFile)) {
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "[NOTFOUND] Blocklist file {$safeJail}.blocklist.json not found.",
                'type' => 'error'
            ];
            continue;
        }

        // Open lock file
        $lockHandle = fopen($lockFile, 'c');
        if (!$lockHandle) {
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "[LOCK] Unable to open lock file for {$safeJail}.",
                'type' => 'error'
            ];
            continue;
        }

        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "[LOCK] Could not acquire lock for {$safeJail}.",
                'type' => 'error'
            ];
            continue;
        }

        // Load existing JSON
        $data = json_decode(file_get_contents($jsonFile), true);
        if (!is_array($data)) {
            $data = [];
        }

        $found = false;
        foreach ($data as &$item) {
            if ($item['ip'] === $ip && (!isset($item['active']) || $item['active'] === true)) {
                $item['active'] = false;
                $item['lastModified'] = date('c');
                $found = true;

                if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $results[] = [
                        'ip' => $ip,
                        'success' => false,
                        'message' => "[WRITE] Failed to update {$safeJail}.blocklist.json.",
                        'type' => 'error'
                    ];
                    continue 2;
                }

                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                $results[] = [
                    'ip' => $ip,
                    'success' => true,
                    'message' => "IP $ip was successfully unblocked in {$safeJail}.blocklist.json.",
                    'type' => 'success'
                ];
                continue 2;
            }
        }
        unset($item);

        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);

        if (!$found) {
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "[NOTFOUND] IP $ip not active in {$safeJail}.blocklist.json.",
                'type' => 'error'
            ];
        }
    }

    // Flatten result if only one entry
    if (count($results) === 1) {
        return $results[0];
    }

    return [
        'success' => true,
        'message' => count($results) . ' IP(s) processed.',
        'details' => $results,
        'type' => 'success'
    ];
}
