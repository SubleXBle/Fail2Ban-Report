// const availableFiles = <?php echo $filesJson; ?>;
const jsonProxyEndpoint = 'includes/get-json.php?file=';
let currentSort = { column: 'timestamp', direction: 'desc' }; // default newest first
let allData = [];        // cached data of current JSON file
let currentFilename = ''; // current loaded filename

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
  if (availableFiles.length) {
    currentFilename = availableFiles[0];
    await loadDataAndRender(currentFilename);
  }
}

async function loadDataAndRender(filename) {
  try {
    const response = await fetch(jsonProxyEndpoint + encodeURIComponent(filename));
    if (!response.ok) throw new Error('Could not load the file');
    allData = await response.json();
    currentFilename = filename;
    renderTable();
  } catch (err) {
    alert('Error loading data: ' + err.message);
  }
}

function renderTable() {
  const tbody = document.querySelector('#resultTable tbody');
  const selectedDate = getSelectedDate();
  const actionFilter = document.getElementById('actionFilter').value;
  const jailFilter = document.getElementById('jailFilter').value;
  const ipFilter = document.getElementById('ipFilter').value.trim();
  const markFilter = document.getElementById('markFilter').value;

  // === Step 1: Filter by base criteria ===
  const filtered = allData.filter(entry => {
    const entryDate = entry.timestamp ? entry.timestamp.substring(0, 10) : '';
    return (!selectedDate || entryDate === selectedDate) &&
           (!actionFilter || entry.action === actionFilter) &&
           (!jailFilter || entry.jail === jailFilter) &&
           (!ipFilter || entry.ip.includes(ipFilter));
  });

  // === Step 2: Prepare marker counts ===
  const eventCounts = {};
  const ipJails = {};
  filtered.forEach(e => {
    const key = e.ip + '|' + e.action;
    eventCounts[key] = (eventCounts[key] || 0) + 1;

    if (!ipJails[e.ip]) ipJails[e.ip] = new Set();
    ipJails[e.ip].add(e.jail);
  });

  // === Step 3: Assign marker field ===
  filtered.forEach(e => {
    let marker = '';
    if (eventCounts[e.ip + '|' + e.action] > 1) marker += '🟡'; // multiple same event
    if (ipJails[e.ip].size > 1) marker += '🔴'; // multiple jails
    if (!marker) marker = '⚪'; // grey dot if no marker
    e.marker = marker;
  });

  // === Step 4: Apply marker filter ===
  const filteredWithMarker = filtered.filter(e => {
    if (!markFilter) return true; // All
    if (markFilter === 'yellow') return e.marker.includes('🟡') && !e.marker.includes('🔴');
    if (markFilter === 'red') return e.marker.includes('🔴') && !e.marker.includes('🟡');
    if (markFilter === 'yellowred') return e.marker.includes('🟡') && e.marker.includes('🔴');
    if (markFilter === 'none') return e.marker === '⚪';
    return true;
  });

  // === Step 5: Rebuild jail dropdown ===
  const jailSelect = document.getElementById('jailFilter');
  const previousSelection = jailSelect.value;
  jailSelect.innerHTML = '';
  const emptyOption = document.createElement('option');
  emptyOption.value = "";
  emptyOption.textContent = "All";
  jailSelect.appendChild(emptyOption);
  const jails = [...new Set(filteredWithMarker.map(e => e.jail).filter(Boolean))].sort();
  jails.forEach(j => {
    const o = document.createElement('option');
    o.value = j;
    o.textContent = j;
    if (j === previousSelection) o.selected = true;
    jailSelect.appendChild(o);
  });
  if (previousSelection && !jails.includes(previousSelection)) {
    jailSelect.value = "";
    renderTable();
    return;
  }

  // === Step 6: Sorting ===
  const sorted = [...filteredWithMarker].sort((a, b) => {
    const { column, direction } = currentSort;
    let valA = a[column] || '';
    let valB = b[column] || '';

    if (column === 'timestamp') {
      valA = Date.parse(valA.replace(' ', 'T').replace(',', '.'));
      valB = Date.parse(valB.replace(' ', 'T').replace(',', '.'));
    } else {
      valA = valA.toString().toLowerCase();
      valB = valB.toString().toLowerCase();
    }

    if (valA < valB) return direction === 'asc' ? -1 : 1;
    if (valA > valB) return direction === 'asc' ? 1 : -1;
    return 0;
  });

  // === Step 7: Table header sort arrows ===
  document.querySelectorAll('#resultTable thead th[data-sort]').forEach(th => {
    const col = th.getAttribute('data-sort');
    const arrow = col === currentSort.column ? (currentSort.direction === 'asc' ? ' ⮝' : ' ⮟') : '';
    th.textContent = th.getAttribute('data-label') + arrow;
  });

  // === Step 8: Render table ===
  tbody.innerHTML = '';
  sorted.forEach(entry => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${entry.timestamp}</td>
      <td>${entry.action}</td>
      <td>${entry.marker}</td>
      <td>${entry.ip}</td>
      <td>${entry.jail}</td>
      <td><input type="checkbox" class="ip-select" data-ip="${entry.ip}" data-jail="${entry.jail}"></td>
    `;
    tbody.appendChild(row);
  });
}

function getSelectedDate() {
  const filename = document.getElementById('dateSelect').value;
  const dateMatch = filename.match(/(\d{4})(\d{2})(\d{2})/);
  return dateMatch ? `${dateMatch[1]}-${dateMatch[2]}-${dateMatch[3]}` : null;
}

// === Event listeners for filters ===
document.getElementById('dateSelect').addEventListener('change', e => loadDataAndRender(e.target.value));
document.getElementById('actionFilter').addEventListener('change', renderTable);
document.getElementById('jailFilter').addEventListener('change', renderTable);
document.getElementById('ipFilter').addEventListener('input', renderTable);
document.getElementById('markFilter').addEventListener('change', renderTable);

// === Event listeners for sorting ===
document.querySelectorAll('#resultTable thead th[data-sort]').forEach(th => {
  th.addEventListener('click', () => {
    const column = th.getAttribute('data-sort');
    if (currentSort.column === column) {
      currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
    } else {
      currentSort.column = column;
      currentSort.direction = 'asc';
    }
    renderTable();
  });
});

// === Initial loading ===
populateDateDropdown();
