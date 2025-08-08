<?php include ('includes/list-files.php'); ?>
<?php include 'includes/header.php'; ?>

  <button class="button-reset" onclick="location.href=location.pathname" title="! reset and reload !">↻</button>

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

  <button class="button-reset" id="banSelectedBtn">Ban</button>
  <button class="button-reset" id="reportSelectedBtn">Info</button>

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
    <p id="blocklistDesc" class="sr-only">Hier können Sie die gebannten IPs verwalten und durchsuchen.</p>


    <div id="blocklistFilters" style="margin-bottom: 1em;">


      <button id="blocklistJailFilterBtn" title="Filter by Jail ▾">Filter by Jail ▾</button>

      <div id="blocklistJailFilterOverlay" class="hidden" style="position:absolute; background:#222; border:1px solid #444; padding:10px; max-height:200px; overflow-y:auto; z-index:1100;">
       <div id="blocklistJailFilterContainer">
      <!-- Checkboxes here -->
       </div>
      </div>




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
