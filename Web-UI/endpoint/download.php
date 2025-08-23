<?php
// download.php – Sicherer Download von Blocklists für Clients

// === Config ===
$CLIENTS_FILE = "/opt/Fail2Ban-Report/Settings/client-list.json";
$BLOCKLIST_BASE = __DIR__ . "/endpoint/"; // Pfad zu den vorbereiteten Blocklists

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

// === 2) Prüfen, ob Blocklists existieren ===
$userBlocklistDir = $BLOCKLIST_BASE . $username . "/blocklists/";
if (!is_dir($userBlocklistDir)) {
    respond(404, ["success" => false, "message" => "No blocklists found for this client."]);
}

$files = glob($userBlocklistDir . "*.json");
if (!$files || count($files) === 0) {
    respond(404, ["success" => false, "message" => "No blocklists available for download."]);
}

// === 3) Dateien ausliefern (eine pro Request) ===
$fileToDownload = $_GET['file'] ?? '';
$fullPath = realpath($userBlocklistDir . $fileToDownload);

if (!$fileToDownload || !$fullPath || strpos($fullPath, realpath($userBlocklistDir)) !== 0 || !file_exists($fullPath)) {
    respond(404, ["success" => false, "message" => "Requested blocklist not found."]);
}

// Header für direkten Download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="' . basename($fullPath) . '"');
header('Content-Length: ' . filesize($fullPath));
readfile($fullPath);
exit;
