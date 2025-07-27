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

  <script>
    // Files from PHP
    const availableFiles = <?php echo $filesJson; ?>;
    const jsonDirectory = './archive/';

    function formatDateFromFilename(filename) {
      const dateStr = filename.match(/(\d{4})(\d{2})(\d{2})/);
      if (!dateStr) return filename;
      const date = new Date(`${dateStr[1]}-${dateStr[2]}-${dateStr[3]}`);
      return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    }

    async function populateDateDropdown() {
      const select = document.getElementById('dateSelect');
      select.innerHTML = '';
      availableFiles.forEach(file => {
        const option = document.createElement('option');
        option.value = file;
        option.textContent = formatDateFromFilename(file);
        select.appendChild(option);
      });
      if (availableFiles.length) loadAndRender(availableFiles[0]);
    }

    async function loadAndRender(filename) {
      try {
        const response = await fetch(jsonDirectory + filename);
        if (!response.ok) throw new Error('Could not load the file');
        const data = await response.json();
        renderTable(data);
      } catch (err) {
        alert('Error loading data: ' + err.message);
      }
    }

    function renderTable(data) {
      const tbody = document.querySelector('#resultTable tbody');
      const actionFilter = document.getElementById('actionFilter').value;
      const jailFilter = document.getElementById('jailFilter').value;
      const ipFilter = document.getElementById('ipFilter').value.trim();

      const filtered = data.filter(entry => {
        return (!actionFilter || entry.action === actionFilter) &&
               (!jailFilter || entry.jail === jailFilter) &&
               (!ipFilter || entry.ip.includes(ipFilter));
      });

      // Populate jail filter only once
      const jails = [...new Set(data.map(e => e.jail))].sort();
      const jailSelect = document.getElementById('jailFilter');
      if (!jailSelect.dataset.populated) {
        // Empty option for "all"
        const emptyOption = document.createElement('option');
        emptyOption.value = "";
        emptyOption.textContent = "All";
        jailSelect.appendChild(emptyOption);

        jails.forEach(j => {
          const o = document.createElement('option');
          o.value = j;
          o.textContent = j;
          jailSelect.appendChild(o);
        });
        jailSelect.dataset.populated = true;
      }

      tbody.innerHTML = '';
      filtered.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${entry.timestamp}</td>
          <td>${entry.action}</td>
          <td>${entry.ip}</td>
          <td>${entry.jail}</td>
        `;
        tbody.appendChild(row);
      });
    }

    // Event listeners
    document.getElementById('dateSelect').addEventListener('change', e => loadAndRender(e.target.value));
    document.getElementById('actionFilter').addEventListener('change', () => loadAndRender(document.getElementById('dateSelect').value));
    document.getElementById('jailFilter').addEventListener('change', () => loadAndRender(document.getElementById('dateSelect').value));
    document.getElementById('ipFilter').addEventListener('input', () => loadAndRender(document.getElementById('dateSelect').value));

    // Initialize
    populateDateDropdown();
  </script>
</body>
</html>
