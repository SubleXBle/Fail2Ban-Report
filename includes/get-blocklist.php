<?php
// includes/get-blocklist.php

header('Content-Type: application/json');

// Absolute path to blocklist.json outside the webroot or in the archive folder
$blocklistPath = dirname(__DIR__) . '/archive/blocklist.json';

// Check if the file exists
if (!file_exists($blocklistPath)) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Blocklist file not found.'
    ]);
    exit;
}

// Read file contents
$data = file_get_contents($blocklistPath);
if ($data === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to read blocklist file.'
    ]);
    exit;
}

// Output raw JSON data
echo $data;
