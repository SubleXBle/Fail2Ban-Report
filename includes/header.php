<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <meta name="viewport" content="width=device-width, initial-scale=0.8">
  <link rel="icon" href="assets/css/faviconx32.png" type="image/png">
  
  <script>
    const availableFiles = <?php echo $filesJson; ?>;
  </script>
  <script>
    const statsFile = 'fail2ban-events-<?php echo date("Ymd"); ?>.json';
  </script>
  <script src="assets/js/jsonreader.js" defer></script>
  <script src="assets/js/action-collector.js" defer></script>
  <script src="assets/js/action.js" defer></script>
  <script src="assets/js/blocklist-overlay.js" defer></script>
  <script src="assets/js/fail2ban-logstats.js" defer></script>
</head>
<body>

<div class="inline-headlines">
  <div>
    <h1>Fail2Ban-Report</h1>
    <h2>Let's catch the bad guys!</h2>
  </div>

  <div id="fail2ban-stats">
    <div>F2B Blocks/Unblocks Today:</div>
    <div>🚫 Bans: <span id="fail2ban-bans">--</span></div>
    <div>🟢 Unbans: <span id="fail2ban-unbans">--</span></div>
    <div>📊 Total: <span id="fail2ban-total">--</span></div>
  </div>

  <div id="headerversion">
    <div><span title="Beta 3.1">Version : 0.3.1</span></div>
  </div>
</div>
