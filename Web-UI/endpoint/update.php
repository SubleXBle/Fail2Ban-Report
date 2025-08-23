<?php
// endpoint/update.php

header('Content-Type: application/json');

require_once __DIR__ . '/../Settings/paths.php'; // optional für globale Pfade
define('CLIENT_LIST', __DIR__ . '/../Settings/client-list.json');
define('UPDATE_FILE', __DIR__ . '/update.json');
define('BLOCKLIST_BASE', __DIR__ . '/../archive/');

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// --- Load client list ---
if (!file_exists(CLIENT_LIST)) {
    respond(['success' => false, 'error' => 'Client list not found'], 500);
}
$clients = json_decode(file_get_contents(CLIENT_LIST), true);
if (!is_array($clients)) {
    respond(['success' => false, 'error' => 'Client list corrupted'], 500);
}

// --- Parse input ---
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['username'], $input['password'])) {
    respond(['success' => false, 'error' => 'Invalid request'], 400);
}
$username = $input['username'];
$password = $input['password'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// --- Authenticate client ---
$authorized = false;
foreach ($clients as $c) {
    if ($c['username'] === $username && password_verify($password, $c['password'])) {
        if (isset($c['ip']) && $c['ip'] !== '' && $c['ip'] !== $client_ip) {
            respond(['success' => false, 'error' => 'IP not allowed'], 403);
        }
        $authorized = true;
        break;
    }
}
if (!$authorized) {
    respond(['success' => false, 'error' => 'Unauthorized'], 401);
}

// --- Load update.json ---
if (!file_exists(UPDATE_FILE)) {
    $update_data = [];
} else {
    $update_data = json_decode(file_get_contents(UPDATE_FILE), true);
    if (!is_array($update_data)) $update_data = [];
}

// --- Check if client has updates ---
$client_updates = $update_data[$username] ?? [];

if (empty($client_updates)) {
    respond(['success' => true, 'updates' => []]);
}

// --- Build response ---
$response = ['success' => true, 'updates' => []];

foreach ($client_updates as $file => $flag) {
    if ($flag !== true) continue;

    // Only allow blocklists
    if (!preg_match('/\.blocklist\.json$/', $file)) continue;

    // Sicherheits-Check für Username
    $username_safe = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);
    $filepath = BLOCKLIST_BASE . $username_safe . '/blocklists/' . $file;

    if (!file_exists($filepath)) {
        continue;
    }

    // Lock the file during read
    $lockHandle = fopen($filepath, 'r');
    if ($lockHandle) {
        if (flock($lockHandle, LOCK_SH)) {
            $content = file_get_contents($filepath);
            $response['updates'][$file] = json_decode($content, true);
            flock($lockHandle, LOCK_UN);
        }
        fclose($lockHandle);
    }
}

// --- Optionally reset update flags ---
foreach (array_keys($client_updates) as $file) {
    $update_data[$username][$file] = false;
}
file_put_contents(UPDATE_FILE, json_encode($update_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

respond($response);
