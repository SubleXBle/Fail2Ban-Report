<?php include ('includes/list-files.php'); ?>
<?php include 'includes/header.php'; ?>

<!-- Blockstats -->
<div class="jaillistdiv">
  <div class="subheadhead">Number of Fail2Ban Blocks per Jail today:</div>
  <div id="fail2ban-bans-per-jail" class="jaillist">loading list ...</div>
</div>
<!-- Future Feature -->
<!--
<div class="jaillistdiv">
  <div class="subheadhead">UFW Blocks from Fail2Ban-Report Lists:</div>
 <div class="jaillist" id="ufw-blocks-info">loading list ..</div>
</div>
-->
<!-- Future Feature -->
<!-- Blockstats -->
<div class="spacer1"></div>

<!-- === Filters Container === -->
<div id="filters" style="display:flex; flex-wrap:wrap; gap:0.5em; align-items:center; margin-bottom:1em;">

  <button class="button-reset" onclick="location.href=location.pathname" title="! reset and reload !">↻</button>

  <label for="dateSelect">Select Date:</label>
  <select id="dateSelect"></select>

  <label for="actionFilter">Action:</label>
  <select id="actionFilter">
    <option value="">All</option>
    <option value="Ban">Ban</option>
    <option value="Unban">Unban</option>
  </select>

  <label for="markFilter">Mark:</label>
  <select id="markFilter">
    <option value="">All</option>
    <option value="yellow">🟡 Ban Increases</option>
    <option value="red">🔴 Multiple Bans</option>
    <option value="yellowred">🟡🔴 Both</option>
    <option value="none">⚪ None</option>
  </select>

  <label for="jailFilter">Jail:</label>
  <select id="jailFilter"></select>

  <label for="ipFilter">IP contains:</label>
  <input type="text" id="ipFilter" placeholder="e.g. 192.168" />

  <button class="button-reset" id="banSelectedBtn">Ban</button>
  <button class="button-reset" id="reportSelectedBtn">Info</button>

  <button id="openBlocklistBtn">Blocklist</button>

  <button class="button-reset" onclick="copyFilteredToClipboard()">📋</button>

</div>

<div id="notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<!--
<div class="jaillistdiv">
  <div class="subheadhead">Number of Fail2Ban Blocks per Jail Today:</div>
  <div id="fail2ban-bans-per-jail" class="jaillist">loading list ...</div>
</div>
<div class="jaillistdiv">
  <div class="subheadhead">UFW Blocks from Fail2Ban-Report Blocklists: </div>
  <div class="jaillist" id="ufw-blocks-info">loading list ..</div>
</div>
-->

<table id="resultTable">
   <thead>
     <tr>
       <th data-sort="timestamp" data-label="Date">Date</th>
       <th data-sort="action" data-label="Action">Action</th>
       <th data-sort="marker" data-label="Mark">Mark</th> <!-- Mark column -->
       <th data-sort="ip" data-label="IP">IP</th>
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
    <p id="blocklistDesc" class="sr-only">Here is your Blocklist</p>

    <div id="blocklistFilters" style="margin-bottom: 1em;">
      <button id="blocklistJailFilterBtn" title="Filter by Jail ▾">Filter by Jail ▾</button>
      <div id="blocklistJailFilterOverlay" class="hidden" style="position:absolute; background:#222; border:1px solid #444; padding:10px; max-height:200px; overflow-y:auto; z-index:1100;">
       <div id="blocklistJailFilterContainer">
        <!-- Checkboxes here -->
       </div>
      </div>

      <input type="date" id="blocklistDateFilter" />
      <button id="blocklistResetBtn" class="button-reset" type="button">Reset</button>
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
