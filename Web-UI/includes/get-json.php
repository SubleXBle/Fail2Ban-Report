<?php
// includes/get-json.php
require_once __DIR__ . '/paths.php';

$filename = basename($_GET['file'] ?? '');
$filepath = $PATHS["fail2ban"] . '/' . $filename;

// secure: it can only read json from archive
if (
    !$filename ||
    !preg_match('/^fail2ban-events-\d{8}\.json$/', $filename) ||
    strpos(realpath($filepath), realpath($PATHS["fail2ban"])) !== 0 ||
    !file_exists($filepath)
) {
    http_response_code(404);
    exit('Not found');
}

// deliver header and json
header('Content-Type: application/json');
readfile($filepath);
