<?php

// Directory with JSON files
$jsonDir = dirname(__DIR__) . '/archive/';

// List all matching JSON files
$files = array_values(array_filter(scandir($jsonDir), function($f) {
    return preg_match('/^fail2ban-events-\d{8}\.json$/', $f);
}));

// Sort files descending (newest first)
rsort($files);

// Output as JSON for JS
$filesJson = json_encode($files);
?>
