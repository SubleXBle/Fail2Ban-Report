<?php
// includes/unblock-ip.php

require_once __DIR__ . "/paths.php";

function unblockIp($ips, $jail = 'unknown') {

    require_once __DIR__ . '/auth.php';
    if (!is_admin()) {
        return [
            'success' => false,
            'message' => 'Unauthorized: Only admin can unblock IPs.',
            'type' => 'error'
        ];
    }

    global $ARCHIVE_ROOT; // Pfad zum Archive-Root
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

        $safeJail = strtolower(preg_replace('/[^a-z0-9_-]/', '', $jail));
        if ($safeJail === '') $safeJail = 'unknown';

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
        if (!is_array($data)) $data = [];

        $found = false;
        foreach ($data as &$item) {
            if ($item['ip'] === $ip && (!isset($item['active']) || $item['active'] === true)) {
                $item['active'] = false;
                $item['pending'] = true;
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

                // --- Update update.json ---
                updateClientJson($jsonFile, $safeJail);

                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
                $results[] = [
                    'ip' => $ip,
                    'success' => true,
                    'message' => "IP $ip successfully unblocked in {$safeJail}.blocklist.json.",
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

    if (count($results) === 1) return $results[0];

    return [
        'success' => true,
        'message' => count($results) . ' IP(s) processed.',
        'details' => $results,
        'type' => 'success'
    ];
}

/**
 * Setzt den Update-Flag in update.json im Archive-Root.
 *
 * @param string $blocklistFile Pfad der Blocklist, um Servernamen zu extrahieren
 * @param string $jail Blocklist-Name
 */
function updateClientJson($blocklistFile, $jail) {
    global $ARCHIVE_ROOT;

    $relativePath = str_replace($ARCHIVE_ROOT, '', $blocklistFile);
    $parts = explode('/', $relativePath);
    $server = $parts[0] ?? 'unknown';

    $updateFile = $ARCHIVE_ROOT . 'update.json';
    if (!is_dir($ARCHIVE_ROOT)) mkdir($ARCHIVE_ROOT, 0755, true);

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
