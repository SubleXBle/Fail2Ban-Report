<?php
// index.php – Endpoint für Fail2Ban-Report

// === Config ===
$CLIENTS_FILE = "/opt/Fail2Ban-Report/Settings/client-list.json";
$ARCHIVE_BASE = __DIR__ . "/archive/"; // anpassen falls nötig

header('Content-Type: application/json');

// === Helper: Antwortfunktion ===
function respond($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// === 1) Authentifizierung ===
if (!file_exists($CLIENTS_FILE)) {
    respond(500, ["success" => false, "message" => "Client list not found."]);
}
$clients = json_decode(file_get_contents($CLIENTS_FILE), true);
if (!is_array($clients)) {
    respond(500, ["success" => false, "message" => "Client list corrupted."]);
}

// Daten aus Request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$uuid     = $_POST['uuid'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

// Client suchen
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

// === 2) Datei-Prüfung ===
if (!isset($_FILES['file'])) {
    respond(400, ["success" => false, "message" => "No file uploaded."]);
}
$uploadedFile = $_FILES['file']['tmp_name'];
$originalName = $_FILES['file']['name'];

if (!is_uploaded_file($uploadedFile)) {
    respond(400, ["success" => false, "message" => "Invalid upload."]);
}

// Typ bestimmen
$isEvents   = preg_match('/^fail2ban-events-\d+\.json$/', $originalName);
$isBlocklist = preg_match('/^[a-z0-9_-]+\.blocklist\.json$/i', $originalName);

if (!$isEvents && !$isBlocklist) {
    respond(400, ["success" => false, "message" => "Invalid filename: $originalName"]);
}

// === 3) Archivpfad vorbereiten ===
$userArchive = $ARCHIVE_BASE . $username . "/";
$targetDir = $userArchive . ($isEvents ? "fail2ban/" : "blocklists/");
if (!is_dir($targetDir) && !mkdir($targetDir, 0770, true)) {
    respond(500, ["success" => false, "message" => "Failed to create target directory."]);
}
$targetFile = $targetDir . $originalName;

// === 4) Fail2Ban Events → einfach überschreiben ===
if ($isEvents) {
    if (!move_uploaded_file($uploadedFile, $targetFile)) {
        respond(500, ["success" => false, "message" => "Failed to save events file."]);
    }
    chown($targetFile, "root");
    chgrp($targetFile, "www-data");
    respond(200, ["success" => true, "message" => "Events file stored."]);
}

// === 5) Blocklist Verarbeitung ===
$newData = json_decode(file_get_contents($uploadedFile), true);
if (!is_array($newData)) {
    respond(400, ["success" => false, "message" => "Invalid JSON in upload."]);
}

$lockFile = "/tmp/" . $originalName . ".lock";
$lockHandle = fopen($lockFile, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX)) {
    respond(500, ["success" => false, "message" => "Could not acquire lock."]);
}

// Alte Daten laden
$archiveData = [];
if (file_exists($targetFile)) {
    $archiveData = json_decode(file_get_contents($targetFile), true);
    if (!is_array($archiveData)) {
        $archiveData = [];
    }
}

//  Vergleiche & Update
$changed = false;
foreach ($archiveData as $key => &$entry) {
    if (!isset($entry['ip'])) continue;
    $ip = $entry['ip'];

    // Case 1: active=true & pending=true -> pending=false wenn in neuer Liste mit pending=false
    if ($entry['active'] === true && $entry['pending'] === true) {
        foreach ($newData as $n) {
            if ($n['ip'] === $ip && $n['pending'] === false) {
                $entry['pending'] = false;
                $entry['lastModified'] = date('c');
                $changed = true;
                break;
            }
        }
    }

    // Case 2: active=false & pending=true -> löschen wenn nicht mehr in neuer Liste
    if ($entry['active'] === false && $entry['pending'] === true) {
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

// Speichern
if ($changed) {
    if (file_put_contents($targetFile, json_encode(array_values($archiveData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        respond(500, ["success" => false, "message" => "Failed to write blocklist."]);
    }
} else {
    // falls es die Datei noch gar nicht gab -> einfach speichern
    if (!file_exists($targetFile)) {
        if (!move_uploaded_file($uploadedFile, $targetFile)) {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            respond(500, ["success" => false, "message" => "Failed to create blocklist."]);
        }
    }
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

chown($targetFile, "root");
chgrp($targetFile, "www-data");

respond(200, ["success" => true, "message" => "Blocklist updated."]);
