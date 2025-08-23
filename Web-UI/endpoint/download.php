<?php
// download.php – Authentifizierter Blocklist-Download (eine Datei pro Request)

// === Konfiguration ===
$CLIENTS_FILE   = "/opt/Fail2Ban-Report/Settings/client-list.json";
$BLOCKLIST_BASE = __DIR__ . "/endpoint/"; // Basis-Pfad zu den Blocklists

// --- Hilfsfunktion für JSON-Antworten ---
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
if (!$client) {
    respond(403, ["success" => false, "message" => "Authentication failed (user/uuid)."]);
}
if (!password_verify($password, $client['password'])) {
    respond(403, ["success" => false, "message" => "Authentication failed (password)."]);
}
if (isset($client['ip']) && $client['ip'] !== $remoteIp) {
    respond(403, ["success" => false, "message" => "Authentication failed (ip mismatch)."]);
}

// === 3) Datei bestimmen ===
$fileToDownload = $_GET['file'] ?? '';
if (!$fileToDownload) {
    respond(400, ["success" => false, "message" => "Missing 'file' parameter."]);
}

$userBlocklistDir = $BLOCKLIST_BASE . $username . "/blocklists/";
$fullPath = realpath($userBlocklistDir . $fileToDownload);

if (!$fullPath || strpos($fullPath, realpath($userBlocklistDir)) !== 0 || !file_exists($fullPath)) {
    respond(404, ["success" => false, "message" => "Requested blocklist not found."]);
}

// === 4) Datei ausliefern ===
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);

// === 5) Datei löschen nach Auslieferung ===
unlink($fullPath);

exit;
