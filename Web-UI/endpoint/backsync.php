<?php
// backsync.php – Receives updated blocklists from the client and synchronizes them on the server

// === Configuration ===
$CLIENTS_FILE    = "/opt/Fail2Ban-Report/Settings/client-list.json";
$ARCHIVE_BASE    = __DIR__ . "/../archive/";
$TEMP_UPLOAD_DIR = sys_get_temp_dir() . "/backsync/";

// === Helper function for JSON responses ===
function respond($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// === 1) Load clients ===
if (!file_exists($CLIENTS_FILE)) {
    respond(500, ["success" => false, "message" => "Client list not found."]);
}

$clients = json_decode(file_get_contents($CLIENTS_FILE), true);
if (!is_array($clients)) {
    respond(500, ["success" => false, "message" => "Client list corrupted."]);
}

// === 2) Authenticate client ===
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
    respond(403, ["success" => false, "message" => "Authentication failed (IP mismatch)."]);
}

// === 3) Prepare temp upload directory ===
$userTempDir = $TEMP_UPLOAD_DIR . $username . "/";
if (!is_dir($userTempDir)) mkdir($userTempDir, 0770, true);

// === 4) Receive files and store them temporarily ===
if (!isset($_FILES['file'])) {
    respond(400, ["success" => false, "message" => "No files uploaded."]);
}

$uploadedFiles = [];
foreach ($_FILES['file']['name'] as $index => $name) {
    $tmpName = $_FILES['file']['tmp_name'][$index];
    if (!is_uploaded_file($tmpName)) continue;
    if (!preg_match('/^[a-z0-9_-]+\.blocklist\.json$/i', $name)) continue;

    $destPath = $userTempDir . basename($name);
    if (!move_uploaded_file($tmpName, $destPath)) continue;
    $uploadedFiles[] = $name;
}

// If no valid files, respond early
if (empty($uploadedFiles)) {
    respond(400, ["success" => false, "message" => "No valid blocklist files uploaded."]);
}

// === 5) Process files immediately, one by one ===
$processedFiles = [];

foreach ($uploadedFiles as $file) {
    $tempFile  = $userTempDir . $file;
    $targetDir = $ARCHIVE_BASE . $username . "/blocklists/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0770, true);
    $targetFile = $targetDir . $file;

    // Acquire lock on the target blocklist file
    $blockLockFile = "/tmp/{$username}_{$file}.lock";
    $blockHandle = fopen($blockLockFile, 'c');
    if (!$blockHandle || !flock($blockHandle, LOCK_EX)) {
        continue; // skip this file if lock cannot be acquired
    }

    // Replace old file with the new one
    copy($tempFile, $targetFile);

    // Acquire lock on update.json
    $updateFile     = $ARCHIVE_BASE . "update.json";
    $updateLockFile = "/tmp/update.json.lock";
    $updateHandle   = fopen($updateLockFile, 'c');
    if ($updateHandle && flock($updateHandle, LOCK_EX)) {
        $updateData = [];
        if (file_exists($updateFile)) {
            $updateData = json_decode(file_get_contents($updateFile), true);
            if (!is_array($updateData)) $updateData = [];
        }

        // Remove the entry for this blocklist
        if (isset($updateData[$username][$file])) {
            unset($updateData[$username][$file]);
            file_put_contents($updateFile, json_encode($updateData, JSON_PRETTY_PRINT));
        }

        flock($updateHandle, LOCK_UN);
        fclose($updateHandle);
    }

    flock($blockHandle, LOCK_UN);
    fclose($blockHandle);

    // Delete temp file
    unlink($tempFile);

    $processedFiles[] = $file;
}

// Optional: clean up temp directory if empty
if (is_dir($userTempDir)) rmdir($userTempDir);

// === 6) Respond to client after all processing ===
respond(200, [
    "success" => true,
    "message" => "All files processed successfully.",
    "files"   => $processedFiles
]);
