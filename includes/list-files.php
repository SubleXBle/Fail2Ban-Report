<?php

// Path to the config file
$configPath = '/opt/Fail2Ban-Report/fail2ban-report.config';

// Read config file and parse the [Fail2Ban-Daily-List-Settings] section
$config = [];
if (file_exists($configPath)) {
    $config = parse_ini_file($configPath, true);
}

// Default max days to show if not set in config
$maxDays = 7;
if (isset($config['Fail2Ban-Daily-List-Settings']['max_display_days'])) {
    $maxDays = (int)$config['Fail2Ban-Daily-List-Settings']['max_display_days'];
}

$jsonDir = dirname(__DIR__) . '/archive/';

// Collect all matching JSON files with their dates extracted from filenames
$matchedFiles = [];
foreach (scandir($jsonDir) as $filename) {
    // Match files like fail2ban-events-YYYYMMDD.json
    if (preg_match('/^fail2ban-events-(\d{8})\.json$/', $filename, $matches)) {
        $matchedFiles[] = [
            'filename' => $filename,
            'date' => $matches[1], // Extracted date as string YYYYMMDD
        ];
    }
}

// Sort files by date descending (newest first)
usort($matchedFiles, function($a, $b) {
    return strcmp($b['date'], $a['date']); // descending order by date string
});

// Take only the latest $maxDays files
$latestFiles = array_slice($matchedFiles, 0, $maxDays);

// Extract only the filenames for JavaScript consumption
$files = array_column($latestFiles, 'filename');

// Encode the list of files as JSON for frontend use
$filesJson = json_encode(array_values($files));
