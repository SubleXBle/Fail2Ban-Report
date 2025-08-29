<!DOCTYPE html>
<html lang="en">
<head>

  <?php require_once __DIR__ . '/auth.php'; ?>

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
  <script src="assets/js/table-export.js"></script>
  <script src="assets/js/ufw-report.js"></script>

<!-- Auth -->
<?php
if (!isset($_SESSION['user_role'])) {
    die("Session not active. Please login.");
}
?>
<!-- Auth -->


</head>
<body>

<div class="sessionheader">

  <div>
    <h1>Fail2Ban-Report</h1>
    <h2>Let's catch the bad guys!</h2>
  <div>
    <span title="Beta 5.0"><small>Version : 0.5.0</small> 🕵️</span></div>
  </div>


<!-- Show User and Server -->
<!--
<div>
<?php
if (isset($_SESSION['username'])) {
    echo "<h3>".$_SESSION['username']." @ ".$activeServer."</h3>";
} else {
    echo "<h4>viewer @ ".$activeServer."</h4>";
}
?>
</div>
-->
<!-- Show User and Server -->


<!-- Log in/out -->

<div>
<form method="post" action="">
  <small>
  <label for="login_user">User:</label>
  <input type="text" name="login_user" id="login_user" required>
  <label for="login_pass">Password:</label>
  <input type="password" name="login_pass" id="login_pass" required>
  </small>
  <button class="button-reset" type="submit">Login</button>
</form>
</div>
<div>
<form method="post" action="">
  <button class="button-reset" type="submit" name="logout" value="1">Logout</button>
</form>
</div>

<!-- Log in/out -->


<!-- Serverselect -->
<div>
<form method="post" style="margin-bottom: 1em;">
    <label for="server">
<?php
if (isset($_SESSION['username'])) {
    echo "<span style='color: #d4af37; font-weight: bold;'>".$_SESSION['username']."</span> @";
} else {
    echo "guest @";
}
?>
    </label>
    <select name="server" id="server" onchange="this.form.submit()">
        <?php
        foreach ($SERVERS as $key => $name) {
            $selected = ($key === $activeServer) ? "selected" : "";
            echo "<option value='$key' $selected>$name</option>";
        }
        ?>
    </select>
</form>
</div>
<!-- Serverselect -->
</div>

<!- Second row here -->

<div class="inline-headlines">


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

  <div class="fail2ban-alerts-container">
    <div class="headhead">Top 3 Bans/Jails:</div>
    <div id="fail2ban-top3-jails" class="toplist"></div>
  </div>



  <div id="fail2ban-stats">
    <div class="headhead">Fail2Ban Today:</div>
    <div>🚫 Bans: <span id="fail2ban-bans">--</span></div>
    <div>🟢 Unbans: <span id="fail2ban-unbans">--</span></div>
    <div>📊 Total: <span id="fail2ban-total">--</span></div>
  </div>

  <div class="history-stats">
    <div class="headhead">Fail2Ban History:</div>
    <div class="headstat">🕓 Yesterd: <span id="fail2ban-yesterday">--</span></div>
    <div class="headstat">📅 7 Days : <span id="fail2ban-last7">--</span></div>
    <div class="headstat">📆 30 Days: <span id="fail2ban-last30">--</span></div>
  </div>


  <div id="blocklist-stats">
  <div class="headhead">Fail2Ban-Report Blocklists:</div>
  <div id="blocklist-stats-container">
    <!-- JS render Jails here -->
  </div>
  </div>


</div>
