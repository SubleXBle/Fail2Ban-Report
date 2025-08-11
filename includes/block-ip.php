<?php
// includes/block-ip.php

/**
 * Blocks one or multiple IP addresses by adding them to their jail-specific blocklist JSON files.
 *
 * @param string|array $ips        IP address or array of IP addresses to block.
 * @param string $jail             Fail2Ban jail/context name (optional).
 * @param string $source           Who triggered the block (e.g. 'manual', 'report', etc.)
 * @return array                   Result array or array of results with 'success', 'message' and 'type'.
 */
function blockIp($ips, $jail = 'unknown', $source = 'manual') {
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

        $jsonFile = dirname(__DIR__, 1) . "/archive/{$safeJail}.blocklist.json";
        $lockFile = "/tmp/{$safeJail}.blocklist.lock"; // same lock file name as shell script

        // Open lock file
        $lockHandle = fopen($lockFile, 'c');
        if (!$lockHandle) {
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "Unable to open lock file for {$safeJail}.",
                'type' => 'error'
            ];
            continue;
        }

        // Acquire exclusive lock
        if (!flock($lockHandle, LOCK_EX)) {
            fclose($lockHandle);
            $results[] = [
                'ip' => $ip,
                'success' => false,
                'message' => "Could not acquire lock for {$safeJail}.",
                'type' => 'error'
            ];
            continue;
        }

        // Load existing JSON
        $data = [];
        if (file_exists($jsonFile)) {
            $existing = file_get_contents($jsonFile);
            $data = json_decode($existing, true);
            if (!is_array($data)) {
                $data = []; // fallback if file is corrupted
            }
        }

        // Check if IP already exists
        $found = false;
        foreach ($data as &$item) {
            if ($item['ip'] === $ip) {
                $found = true;
                if (!isset($item['active']) || $item['active'] === false) {
                    $item['active'] = true;
                    $item['lastModified'] = date('c');
                    if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                        flock($lockHandle, LOCK_UN);
                        fclose($lockHandle);
                        $results[] = [
                            'ip' => $ip,
                            'success' => false,
                            'message' => "Failed to write to {$safeJail}.blocklist.json.",
                            'type' => 'error'
                        ];
                        continue 2;
                    }
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $results[] = [
                        'ip' => $ip,
                        'success' => true,
                        'message' => "IP $ip was reactivated in {$safeJail}.blocklist.json.",
                        'type' => 'success'
                    ];
                    continue 2;
                } else {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $results[] = [
                        'ip' => $ip,
                        'success' => true,
                        'message' => "IP $ip is already active in {$safeJail}.blocklist.json.",
                        'type' => 'info'
                    ];
                    continue 2;
                }
            }
        }
        unset($item);

        // Add new entry if not found
        if (!$found) {
            $entry = [
                'ip' => $ip,
                'jail' => $safeJail,
                'source' => $source,
                'timestamp' => date('c'),
                'expires' => null,
                'reason' => '',
                'active' => true,
                'lastModified' => date('c'),
                'tags' => [],
                'pending' => true
            ];
            $data[] = $entry;

            if (file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                $results[] = [
                    'ip' => $ip,
                    'success' => false,
                    'message' => "Failed to write to {$safeJail}.blocklist.json.",
                    'type' => 'error'
                ];
                continue;
            }

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            $results[] = [
                'ip' => $ip,
                'success' => true,
                'message' => "IP $ip was successfully added to {$safeJail}.blocklist.json.",
                'type' => 'success'
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
