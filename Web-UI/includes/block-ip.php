<?php
// includes/block-ip.php

require_once __DIR__ . "/paths.php";

function blockIp($ips, $jail = 'unknown', $source = 'manual') {
    if (!is_admin()) {
        return [
            'success' => false,
            'message' => 'Unauthorized: Only admin can block IPs.',
            'type' => 'error'
        ];
    }

    global $ARCHIVE_ROOT; // Archive-Root
    $results = [];

    if (!is_array($ips)) {
        $ips = [$ips];
    }

    foreach ($ips as $ip) {
        $ip = trim($ip);

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
        if ($safeJail === '') $safeJail = 'unknown';

        // path to Blocklist
        $jsonFile = $GLOBALS["PATHS"]["blocklists"] . $safeJail . ".blocklist.json";
        $lockFile = "/tmp/{$safeJail}.blocklist.lock";

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

        // load used blocklist
        $data = [];
        if (file_exists($jsonFile)) {
            $existing = file_get_contents($jsonFile);
            $data = json_decode($existing, true);
            if (!is_array($data)) $data = [];
        }

        $found = false;
        foreach ($data as &$item) {
            if ($item['ip'] === $ip) {
                $found = true;
                if (!isset($item['active']) || $item['active'] === false) {
                    $item['active'] = true;
                    $item['lastModified'] = date('c');
                    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

                    // --- Update update.json ---
                    updateClientJson($jsonFile, $safeJail);

                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $results[] = [
                        'ip' => $ip,
                        'success' => true,
                        'message' => "IP $ip reactivated in {$safeJail}.blocklist.json.",
                        'type' => 'success'
                    ];
                    continue 2;
                } else {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                    $results[] = [
                        'ip' => $ip,
                        'success' => true,
                        'message' => "IP $ip already active in {$safeJail}.blocklist.json.",
                        'type' => 'info'
                    ];
                    continue 2;
                }
            }
        }
        unset($item);

        // add new ip
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

            file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // --- Update update.json ---
            updateClientJson($jsonFile, $safeJail);

            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            $results[] = [
                'ip' => $ip,
                'success' => true,
                'message' => "IP $ip added to {$safeJail}.blocklist.json.",
                'type' => 'success'
            ];
        }
    }

    if (count($results) === 1) return $results[0];

    return [
        'success' => true,
        'message' => count($results) . ' IP(s) processed.',
        'details' => $results,
        'type' => 'success'
    ];
}

/**
 * set Update-Flag in update.json in Archive-Root.
 *
 * @param string $blocklistFile Pfad der Blocklist, um Servernamen zu extrahieren
 * @param string $jail Blocklist-Name
 */
function updateClientJson($blocklistFile, $jail) {
    global $ARCHIVE_ROOT;

    // Servername from path
    $relativePath = str_replace($ARCHIVE_ROOT, '', $blocklistFile);
    $parts = explode('/', $relativePath);
    $server = $parts[0] ?? 'unknown';

    $updateFile = $ARCHIVE_ROOT . 'update.json';
    if (!is_dir($ARCHIVE_ROOT)) mkdir($ARCHIVE_ROOT, 0755, true);

    // Load or initialize
    if (file_exists($updateFile)) {
        $update_data = json_decode(file_get_contents($updateFile), true);
        if (!is_array($update_data)) $update_data = [];
    } else {
        $update_data = [];
    }

    if (!isset($update_data[$server])) $update_data[$server] = [];

    $update_data[$server][$jail . '.blocklist.json'] = true;

    file_put_contents($updateFile, json_encode($update_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
