// Files from PHP
    //const availableFiles = <?php echo $filesJson; ?>;
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
        const dateMatch = filename.match(/(\d{4})(\d{2})(\d{2})/);
        const selectedDate = dateMatch ? `${dateMatch[1]}-${dateMatch[2]}-${dateMatch[3]}` : null;
        renderTable(data, selectedDate);
      } catch (err) {
        alert('Error loading data: ' + err.message);
      }
    }

    function renderTable(data, selectedDate) {
      const tbody = document.querySelector('#resultTable tbody');
      const actionFilter = document.getElementById('actionFilter').value;
      const jailFilter = document.getElementById('jailFilter').value;
      const ipFilter = document.getElementById('ipFilter').value.trim();

      // Show only the selected Date (Format: "2025-07-29")
      const filtered = data.filter(entry => {
        const entryDate = entry.timestamp ? entry.timestamp.substring(0, 10) : '';
        return (!selectedDate || entryDate === selectedDate) &&
               (!actionFilter || entry.action === actionFilter) &&
               (!jailFilter || entry.jail === jailFilter) &&
               (!ipFilter || entry.ip.includes(ipFilter));
      });


      // Populate jail Filter

    // Dynamically populate jail filter from filtered data
    const jailSelect = document.getElementById('jailFilter');
    const previousSelection = jailSelect.value; // Save old selection
    jailSelect.innerHTML = ''; // Reset

    const emptyOption = document.createElement('option');
    emptyOption.value = "";
    emptyOption.textContent = "All";
    jailSelect.appendChild(emptyOption);

    // Extract jails only from *visible data* (nach Datum & Action/IP-Filter)
    const jails = [...new Set(filtered.map(e => e.jail).filter(Boolean))].sort();

    jails.forEach(j => {
      const o = document.createElement('option');
      o.value = j;
      o.textContent = j;
      if (j === previousSelection) o.selected = true;
      jailSelect.appendChild(o);
    });

// when previous selection is not there anymore → back to "All"
if (previousSelection && !jails.includes(previousSelection)) {
  jailSelect.value = "";
}


      tbody.innerHTML = '';
      filtered.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${entry.timestamp}</td>
          <td>${entry.action}</td>
          <td>${entry.ip}</td>
          <td>${entry.jail}</td>
          <td><button class="action-btn" data-ip="${entry.ip}" data-jail="${entry.jail}">Action!</button></td>
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
