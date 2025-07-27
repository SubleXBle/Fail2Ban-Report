<?php
// index.php

// Directory with JSON files
$jsonDir = __DIR__ . '/archive/';

// List all matching JSON files
$files = array_values(array_filter(scandir($jsonDir), function($f) {
    return preg_match('/^fail2ban-events-\d{8}\.json$/', $f);
}));

// Sort files descending (newest first)
rsort($files);

// Output as JSON for JS
$filesJson = json_encode($files);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <h1>Fail2Ban Report</h1>
  <h2>Let's catch the bad guys!</h2>

  <label for="dateSelect">Select Date:</label>
  <select id="dateSelect"></select>

  <label for="actionFilter">Action:</label>
  <select id="actionFilter">
    <option value="">All</option>
    <option value="Ban">Ban</option>
    <option value="Unban">Unban</option>
  </select>

  <label for="jailFilter">Jail:</label>
  <select id="jailFilter"></select>

  <label for="ipFilter">IP contains:</label>
  <input type="text" id="ipFilter" placeholder="e.g. 192.168" />

  <table id="resultTable">
    <thead>
      <tr>
        <th>Time</th>
        <th>Action</th>
        <th>IP</th>
        <th>Jail</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

</body>
</html>
