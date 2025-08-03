<?php
// includes/get-json.php

$filename = basename($_GET['file'] ?? '');
$filepath = realpath(__DIR__ . '/../archive/' . $filename);

// secure: it can only read json form archive
if (
    !$filename ||
    !preg_match('/^fail2ban-events-\d{8}\.json$/', $filename) ||
    strpos($filepath, realpath(__DIR__ . '/../archive/')) !== 0 ||
    !file_exists($filepath)
) {
    http_response_code(404);
    exit('Not found');
}

// deliver header and json
header('Content-Type: application/json');
readfile($filepath);
