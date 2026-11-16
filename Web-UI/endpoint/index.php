<?php
// index.php – Endpoint für Fail2Ban-Report Event-Lists

// === Config ===
$CLIENTS_FILE = "/opt/Fail2Ban-Report/Settings/client-list.json";
$ARCHIVE_BASE = __DIR__ . "/archive/"; // anpassen falls nötig

header('Content-Type: application/json');

// === Helper: Response-function ===
function respond($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// === Helper: Validate JSON Entries ===
function validateEntries($data, $isEvents) {
    if (!is_array($data)) {
        return "JSON root must be an array.";
    }

    $allowedActions = ['Ban', 'Unban', 'Increase Ban', 'Restore Ban', 'Already Banned'];

    foreach ($data as $index => $entry) {
        if (!is_array($entry)) {
            return "Entry at index $index is not an object.";
        }

        // 1. Validate IP (required for both events and blocklists)
        if (!isset($entry['ip']) || !is_string($entry['ip']) || filter_var($entry['ip'], FILTER_VALIDATE_IP) === false) {
            return "Invalid IP address at index $index.";
        }

        // 2. Validate Jail (required for events, optional for blocklists)
        if ($isEvents || isset($entry['jail'])) {
            if (!isset($entry['jail']) || !is_string($entry['jail']) || !preg_match('/^[a-z0-9_-]{1,64}$/i', $entry['jail'])) {
                return "Invalid jail name at index $index.";
            }
        }

        // 3. Validate Event-specific fields
        if ($isEvents) {
            if (!isset($entry['action']) || !is_string($entry['action']) || !in_array($entry['action'], $allowedActions, true)) {
                return "Invalid action at index $index.";
            }

            if (!isset($entry['timestamp']) || !is_string($entry['timestamp']) || !preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:[.,]\d+)?(?:Z|[+-]\d{2}:?\d{2})?$/', $entry['timestamp'])) {
                return "Invalid timestamp at index $index.";
            }
        }

        // 4. Validate optional string fields (source, reason, lastModified)
        foreach (['source', 'reason'] as $optField) {
            if (isset($entry[$optField]) && (!is_string($entry[$optField]) || strlen($entry[$optField]) > 255)) {
                return "Invalid $optField at index $index.";
            }
        }
    }

    return true;
}

// === 1) Authentication ===
if (!file_exists($CLIENTS_FILE)) {
    respond(500, ["success" => false, "message" => "Client list not found."]);
}
$clients = json_decode(file_get_contents($CLIENTS_FILE), true);
if (!is_array($clients)) {
    respond(500, ["success" => false, "message" => "Client list corrupted."]);
}

// Data from Request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$uuid     = $_POST['uuid'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

// Search Client
$client = null;
foreach ($clients as $c) {
    if ($c['username'] === $username && $c['uuid'] === $uuid) {
        $client = $c;
        break;
    }
}
if (!$client) {
    respond(403, ["success" => false, "message" => "Authentication failed (user/uuid)."]);
}
if (!password_verify($password, $client['password'])) {
    respond(403, ["success" => false, "message" => "Authentication failed (password)."]);
}
if (isset($client['ip']) && $client['ip'] !== $remoteIp) {
    respond(403, ["success" => false, "message" => "Authentication failed (ip mismatch)."]);
}

// === 2) Check files ===
if (!isset($_FILES['file'])) {
    respond(400, ["success" => false, "message" => "No file uploaded."]);
}
$uploadedFile = $_FILES['file']['tmp_name'];
$originalName = $_FILES['file']['name'];

if (!is_uploaded_file($uploadedFile)) {
    respond(400, ["success" => false, "message" => "Invalid upload."]);
}

// Get type
$isEvents    = (bool) preg_match('/^fail2ban-events-\d+\.json$/', $originalName);
$isBlocklist = (bool) preg_match('/^[a-z0-9_-]+\.blocklist\.json$/i', $originalName);

if (!$isEvents && !$isBlocklist) {
    respond(400, ["success" => false, "message" => "Invalid filename: $originalName"]);
}

// Parse & validate JSON payload BEFORE touching disk
$rawContent = file_get_contents($uploadedFile);
$newData = json_decode($rawContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    respond(400, ["success" => false, "message" => "Malformed JSON in upload."]);
}

$validationResult = validateEntries($newData, $isEvents);
if ($validationResult !== true) {
    respond(400, ["success" => false, "message" => "Validation failed: " . $validationResult]);
}

// === 3) Prepare archive path ===
$safeUsername = basename($username);
$userArchive = $ARCHIVE_BASE . $safeUsername . "/";
$targetDir = $userArchive . ($isEvents ? "fail2ban/" : "blocklists/");
if (!is_dir($targetDir) && !mkdir($targetDir, 0770, true)) {
    respond(500, ["success" => false, "message" => "Failed to create target directory."]);
}
$targetFile = $targetDir . basename($originalName);

// === 4) Fail2Ban Events -> overwrite with re-encoded validated JSON ===
if ($isEvents) {
    $cleanJson = json_encode(array_values($newData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($targetFile, $cleanJson, LOCK_EX) === false) {
        respond(500, ["success" => false, "message" => "Failed to save events file."]);
    }
    @chown($targetFile, "root");
    @chgrp($targetFile, "www-data");
    respond(200, ["success" => true, "message" => "Events file stored."]);
}

// === 5) Process blocklists ===
$lockFile = "/tmp/" . basename($originalName) . ".lock";
$lockHandle = fopen($lockFile, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
    respond(500, ["success" => false, "message" => "Could not acquire lock."]);
}

// Load old Data
$archiveData = [];
if (file_exists($targetFile)) {
    $archiveData = json_decode(file_get_contents($targetFile), true);
    if (!is_array($archiveData)) {
        $archiveData = [];
    }
}

// Compare & Update
$changed = false;
foreach ($archiveData as $key => &$entry) {
    if (!isset($entry['ip'])) continue;
    $ip = $entry['ip'];

    // Case 1: active=true & pending=true -> pending=false when in new List with pending=false
    if (($entry['active'] ?? false) === true && ($entry['pending'] ?? false) === true) {
        foreach ($newData as $n) {
            if ($n['ip'] === $ip && ($n['pending'] ?? null) === false) {
                $entry['pending'] = false;
                $entry['lastModified'] = date('c');
                $changed = true;
                break;
            }
        }
    }

    // Case 2: active=false & pending=true -> delete if not in List anymore
    if (($entry['active'] ?? true) === false && ($entry['pending'] ?? false) === true) {
        $found = false;
        foreach ($newData as $n) {
            if ($n['ip'] === $ip) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            unset($archiveData[$key]);
            $changed = true;
        }
    }
}
unset($entry);

// Save
if ($changed) {
    if (file_put_contents($targetFile, json_encode(array_values($archiveData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        respond(500, ["success" => false, "message" => "Failed to write blocklist."]);
    }
} else {
    // If file is new -> save validated & re-encoded JSON
    if (!file_exists($targetFile)) {
        $cleanJson = json_encode(array_values($newData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($targetFile, $cleanJson) === false) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            respond(500, ["success" => false, "message" => "Failed to create blocklist."]);
        }
    }
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

@chown($targetFile, "root");
@chgrp($targetFile, "www-data");

respond(200, ["success" => true, "message" => "Blocklist updated."]);
