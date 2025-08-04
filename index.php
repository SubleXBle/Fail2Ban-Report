<?php include ('includes/list-files.php'); ?>
<?php // Version 0.2.2 ?>
<?php include 'includes/header.php'; ?>

<button class="siterel" onclick="location.href=location.pathname" title="! reset and reload !">↻</button>

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

  <label><input type="checkbox" name="actions" value="ban"><small>Ban IP</small></label>
  <label><input type="checkbox" name="actions" value="report"><small>Report</small></label>

  <button id="openBlocklistBtn">Edit Blocklist</button>

  <div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

  <table id="resultTable">
    <thead>
       <tr>
         <th data-sort="timestamp" data-label="Date">Date</th>
         <th data-sort="action" data-label="Action">Action</th>
         <th>IP</th>
         <th data-sort="jail" data-label="Jail">Jail</th>
         <th></th>
       </tr>
     </thead>
    <tbody></tbody>
  </table>

  <!-- Edit Blocklist Overlay -->
<div id="blocklistOverlay" class="overlay hidden" role="dialog" aria-modal="true" aria-labelledby="blocklistTitle" aria-describedby="blocklistDesc">
  <div class="overlay-content">
    <h2 id="blocklistTitle">Edit Blocklist</h2>
    <p id="blocklistDesc" class="sr-only">Here you can manage your blocklist.</p>

    <div id="blocklistFilters" style="margin-bottom: 1em;">
      <input type="date" id="blocklistDateFilter" />
      <button id="blocklistResetBtn" class="button-reset"  type="button">Reset</button>
      <input type="text" id="blocklistSearch" placeholder="Search IP or jail" />
    </div>


    <button id="closeOverlayBtn" class="close-btn" aria-label="Close Blocklist Overlay">× Close</button>

    <div id="blocklistContainer">Loading blocklist...</div>

    <button id="reloadBlocklistBtn">Reload Blocklist</button>

  </div>
</div>
<?php include 'includes/footer.php'; ?>


</body>
</html>
