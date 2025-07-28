<?php include(__DIR__ . '/includes/list-files.php'); ?>
<?php // index.php Version 0.2.1 ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <link rel="stylesheet" href="/assets/css/style.css" />
  <script>
    const availableFiles = <?php echo $filesJson; ?>;
  </script>
  <script src="/assets/js/jsonreader.js" defer></script>
  <script src="/assets/js/action-collector.js" defer></script>
  <script src="/assets/js/actions.js" defer></script>
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

  <label><input type="checkbox" name="actions" value="ban"> Ban IP</label>
  <label><input type="checkbox" name="actions" value="report"> Report</label>

  <button id="openBlocklistBtn">Edit Blocklist</button>

  <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>
  
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

  <!-- Edit Blocklist Overlay -->
<div id="blocklistOverlay" class="overlay hidden" role="dialog" aria-modal="true" aria-labelledby="blocklistTitle" aria-describedby="blocklistDesc">
  <div class="overlay-content">
    <h2 id="blocklistTitle">Edit Blocklist</h2>
    <p id="blocklistDesc" class="sr-only">Hier können Sie die gebannten IPs verwalten und durchsuchen.</p>

    <label for="blocklistSearch">Search IP or Jail:</label>
    <input type="text" id="blocklistSearch" placeholder="Type to filter..." />

    <button id="closeOverlayBtn" class="close-btn" aria-label="Close Blocklist Overlay">× Close</button>

    <div id="blocklistContainer">Loading blocklist...</div>

    <button id="reloadBlocklistBtn">Reload Blocklist</button>
    <button id="reapplyFirewallBtn">Reapply Blocklist to Firewall</button>

  </div>
</div>

  
</body>
</html>
