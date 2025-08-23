<?php
// backsync.php – Empfängt aktualisierte Blocklists vom Client und synchronisiert diese auf den Server

// === Konfiguration ===
$CLIENTS_FILE   = "/opt/Fail2Ban-Report/Settings/client-list.json";
$ARCHIVE_BASE   = __DIR__ . "/../archive/";
$TEMP_UPLOAD_DIR = sys_get_temp_dir() . "/backsync/";

// === Hilfsfunktion für JSON-Antworten ===
function respond($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// === 1) Clients laden ===
if (!file_exists($CLIENTS_FILE)) {
    respond(500, ["success" => false, "message" => "Client list not found."]);
}

$clients = json_decode(file_get_contents($CLIENTS_FILE), true);
if (!is_array($clients)) {
    respond(500, ["success" => false, "message" => "Client list corrupted."]);
}

// === 2) Authentifizierung ===
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$uuid     = $_POST['uuid'] ?? '';
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

$client = null;
foreach ($clients as $c) {
    if ($c['username'] === $username && $c['uuid'] === $uuid) {
        $client = $c;
        break;
    }
}
if (!$client) respond(403, ["success" => false, "message" => "Authentication failed (user/uuid)."]);
if (!password_verify($password, $client['password'])) {
    respond(403, ["success" => false, "message" => "Authentication failed (password)."]);
}
if (isset($client['ip']) && $client['ip'] !== $remoteIp) {
    respond(403, ["success" => false, "message" => "Authentication failed (ip mismatch)."]);
}

// === 3) Temp-Pfad vorbereiten ===
$userTempDir = $TEMP_UPLOAD_DIR . $username . "/";
if (!is_dir($userTempDir)) mkdir($userTempDir, 0770, true);

// === 4) Dateien entgegennehmen und temporär speichern ===
if (!isset($_FILES['file'])) {
    respond(400, ["success" => false, "message" => "No files uploaded."]);
}

$uploadedFiles = [];
foreach ($_FILES['file']['name'] as $index => $name) {
    $tmpName = $_FILES['file']['tmp_name'][$index];
    if (!is_uploaded_file($tmpName)) continue;
    if (!preg_match('/^[a-z0-9_-]+\.blocklist\.json$/i', $name)) continue;

    $destPath = $userTempDir . basename($name);
    if (!move_uploaded_file($tmpName, $destPath)) {
        continue;
    }
    $uploadedFiles[] = $name;
}

// Sofortige Rückmeldung an Client
respond(200, [
    "success" => true,
    "message" => "Files uploaded, processing in background",
    "files" => $uploadedFiles
]);

// === 5) Hintergrundverarbeitung ===
foreach ($uploadedFiles as $file) {
    $tempFile = $userTempDir . $file;
    $targetDir = $ARCHIVE_BASE . $username . "/blocklists/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0770, true);
    $targetFile = $targetDir . $file;

    // Lock auf Ziel-Datei
    $blockLockFile = "/tmp/{$username}_{$file}.lock";
    $blockHandle = fopen($blockLockFile, 'c');
    if ($blockHandle && flock($blockHandle, LOCK_EX)) {

        // Alte Datei durch neue ersetzen
        copy($tempFile, $targetFile);

        // Lock auf update.json setzen
        $updateFile = $ARCHIVE_BASE . "update.json";
        $updateLockFile = "/tmp/update.json.lock";
        $updateHandle = fopen($updateLockFile, 'c');
        if ($updateHandle && flock($updateHandle, LOCK_EX)) {
            $updateData = [];
            if (file_exists($updateFile)) {
                $updateData = json_decode(file_get_contents($updateFile), true);
                if (!is_array($updateData)) $updateData = [];
            }

            // Eintrag für diese Blocklist entfernen
            if (isset($updateData[$username][$file])) {
                unset($updateData[$username][$file]);
                file_put_contents($updateFile, json_encode($updateData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            flock($updateHandle, LOCK_UN);
            fclose($updateHandle);
        }

        flock($blockHandle, LOCK_UN);
        fclose($blockHandle);

        // Temp-Datei löschen
        unlink($tempFile);
    }
}

// Optional: Temp-Verzeichnis säubern, falls leer
if (is_dir($userTempDir)) rmdir($userTempDir);
