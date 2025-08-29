document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('openBlocklistBtn');
  const overlay = document.getElementById('blocklistOverlay');
  const closeBtn = document.getElementById('closeOverlayBtn');
  const reloadBtn = document.getElementById('reloadBlocklistBtn');
  const container = document.getElementById('blocklistContainer');
  const searchInput = document.getElementById('blocklistSearch');
  const dateInput = document.getElementById('blocklistDateFilter');
  const resetBtn = document.getElementById('blocklistResetBtn');

  // New: Elements for jail filter overlay
  const jailFilterBtn = document.getElementById('blocklistJailFilterBtn');
  const jailFilterOverlay = document.getElementById('blocklistJailFilterOverlay');
  const jailFilterContainer = document.getElementById('blocklistJailFilterContainer');

  let blocklistData = [];
  let selectedJails = new Set();

  if (!openBtn || !overlay || !closeBtn) {
    console.warn("Overlay elements missing.");
    return;
  }

  // Open main blocklist overlay
  openBtn.addEventListener('click', () => {
    overlay.classList.remove('hidden');
    loadBlocklist();
  });

  // Close main overlay
  closeBtn.addEventListener('click', () => {
    overlay.classList.add('hidden');
  });

  reloadBtn?.addEventListener('click', loadBlocklist);

  searchInput?.addEventListener('input', () => {
    renderBlocklist(blocklistData, searchInput.value.trim(), Array.from(selectedJails));
  });

  dateInput?.addEventListener('input', () => {
    renderBlocklist(blocklistData, searchInput.value.trim(), Array.from(selectedJails));
  });

  resetBtn?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (dateInput) dateInput.value = '';

    // Reset all jails selected
    selectedJails = new Set();
    Array.from(jailFilterContainer.querySelectorAll('input[type="checkbox"]')).forEach(checkbox => {
      checkbox.checked = true;
      selectedJails.add(checkbox.value);
    });

    renderBlocklist(blocklistData, '', Array.from(selectedJails));
  });

  // Toggle jail filter overlay visibility
  jailFilterBtn?.addEventListener('click', () => {
    jailFilterOverlay.classList.toggle('hidden');
  });

  // Close jail filter if clicking outside
  document.addEventListener('click', (e) => {
    if (
      jailFilterOverlay &&
      !jailFilterOverlay.classList.contains('hidden') &&
      !jailFilterOverlay.contains(e.target) &&
      e.target !== jailFilterBtn
    ) {
      jailFilterOverlay.classList.add('hidden');
    }
  });

  // Load blocklist data and setup jail filter checkboxes
  function loadBlocklist() {
    container.textContent = 'Loading blocklist...';
    fetch('includes/get-blocklist.php', { cache: 'no-store' })
      .then(res => {
        if (!res.ok) throw new Error('Failed to load blocklist');
        return res.json();
      })
      .then(data => {
        blocklistData = data.entries || [];

        // Extract unique jails, sorted
        const uniqueJails = [...new Set(blocklistData.map(e => e.jail || 'unknown'))].sort();

        // Setup jail filter checkbox list
        setupJailFilter(uniqueJails);

        // Select all jails by default
        selectedJails = new Set(uniqueJails);

        renderBlocklist(blocklistData, searchInput?.value.trim() || '', uniqueJails);
      })
      .catch(err => {
        container.textContent = 'Error loading blocklist: ' + err.message;
      });
  }

  // Create jail filter checkboxes dynamically
  function setupJailFilter(jails) {
    if (!jailFilterContainer) return;

    jailFilterContainer.innerHTML = ''; // Clear previous

    jails.forEach(jail => {
      const label = document.createElement('label');
      label.style.display = 'block';
      label.style.cursor = 'pointer';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.value = jail;
      checkbox.checked = true;

      checkbox.addEventListener('change', () => {
        if (checkbox.checked) {
          selectedJails.add(jail);
        } else {
          selectedJails.delete(jail);
        }
        renderBlocklist(blocklistData, searchInput?.value.trim() || '', Array.from(selectedJails));
      });

      label.appendChild(checkbox);
      label.appendChild(document.createTextNode(' ' + jail));
      jailFilterContainer.appendChild(label);
    });
  }

  // Render filtered blocklist entries
  function renderBlocklist(data, filter = '', selectedJailsArr = []) {
    if (!Array.isArray(data) || data.length === 0) {
      container.textContent = 'Blocklist is empty.';
      return;
    }

    const activeEntries = data.filter(entry => entry.active !== false);
    const term = filter.toLowerCase();
    const selectedDate = dateInput?.value;

    const filteredData = activeEntries.filter(entry => {
      const jailName = entry.jail || 'unknown';

      const ipMatch = entry.ip.toLowerCase().includes(term);
      const jailMatch = jailName.toLowerCase().includes(term);

      let dateMatch = true;
      if (selectedDate && entry.timestamp) {
        const entryDate = new Date(entry.timestamp).toISOString().split('T')[0];
        dateMatch = entryDate === selectedDate;
      }

      return (ipMatch || jailMatch) && dateMatch && selectedJailsArr.includes(jailName);
    });

    if (filteredData.length === 0) {
      container.textContent = 'No entries match your search.';
      return;
    }

    container.innerHTML = '';
    filteredData.forEach(entry => {
      const div = document.createElement('div');
      div.className = 'blocklist-entry';

      const jailLabel = entry.jail || 'unknown';
      const timeLabel = entry.timestamp
        ? new Date(entry.timestamp).toLocaleString()
        : 'unknown time';

      div.innerHTML = `
       <span>${entry.ip} (Jail: ${jailLabel}) – Blocked at: ${timeLabel}</span>
       <button data-ip="${entry.ip}" data-jail="${jailLabel}">Unblock</button>
     `;


      const btn = div.querySelector('button');
      btn.addEventListener('click', () => unblockIp(entry.ip, jailLabel));
      container.appendChild(div);
    });
  }

  // Unblock IP action
  function unblockIp(ip, jail) {
  fetch('includes/actions/action_unban-ip.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ ip, jail })
  })
  .then(res => res.json())
  .then(data => {
    showNotification(data.message, !data.success);
    if (data.success) loadBlocklist();
    })
    .catch(err => {
      showNotification('Error unblocking IP: ' + err.message, true);
    });
  }

});
