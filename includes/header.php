<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=0.8">
  <link rel="stylesheet" href="assets/css/style.css" />
  <link rel="icon" href="assets/css/favicon-32x32.png" type="image/png">
  <script>
    const availableFiles = <?php echo $filesJson; ?>;
  </script>
  <script>
    const statsFile = 'fail2ban-events-<?php echo date("Ymd"); ?>.json';
  </script>
  <script src="assets/js/jsonreader.js" defer></script>
  <script src="assets/js/notifications.js"></script>
  <script src="assets/js/action-collector.js" defer></script>
  <script src="assets/js/action.js" defer></script>
  <script src="assets/js/blocklist-overlay.js" defer></script>
  <script src="assets/js/fail2ban-logstats.js" defer></script>
  <script src="assets/js/blocklist-stats.js"></script>
  <script src="assets/js/warnings.js"></script>


</head>
<body>

<div class="inline-headlines">
  <div>
    <h1>Fail2Ban-Report</h1>
    <h2>Let's catch the bad guys!</h2>
    <div><span title="Beta 3.4"><small>Version : 0.3.4</small></span></div>
  </div>


  <div id="fail2ban-alerts-container">
    <div class="headhead"><span title="Shows Jail | Events | Unique IPs">DoS/Scan/BF:</span></div>
    <div id="fail2ban-warning-status" class="fail2ban-status">
      <span class="status-dot yellow" id="warning-dot" title="No warnings">🟡</span>
      <span class="status-label" id="warning-label">none</span>
    </div>
    <div id="fail2ban-critical-status" class="fail2ban-status">
      <span class="status-dot red" id="critical-dot" title="No criticals">🔴</span>
      <span class="status-label" id="critical-label">none</span>
    </div>
  </div>


  <div id="fail2ban-stats">
    <div class="headhead">F2B Blocks/Unblocks Today:</div>
    <div>🚫 Bans: <span id="fail2ban-bans">--</span></div>
    <div>🟢 Unbans: <span id="fail2ban-unbans">--</span></div>
    <div>📊 Total: <span id="fail2ban-total">--</span></div>
  </div>

  <div class="history-stats">
    <div class="headhead">F2B Blocks/Unblocks History:</div>
    <div class="headstat">🕓 Yesterday: <span id="fail2ban-yesterday">--</span></div>
    <div class="headstat">📅 Last 7: <span id="fail2ban-last7">--</span></div>
    <div class="headstat">📆 Last 30: <span id="fail2ban-last30">--</span></div>
  </div>

  <div id="blocklist-stats">
  <div class="headhead">Fail2Ban-Report Jail-Overview:</div>
  <div id="blocklist-stats-container">
    <!-- JS render Jails here -->
  </div>
  </div>

</div>
