document.addEventListener('DOMContentLoaded', () => {
  const openBtn = document.getElementById('openBlocklistBtn');
  const overlay = document.getElementById('blocklistOverlay');
  const closeBtn = document.getElementById('closeOverlayBtn');
  const reloadBtn = document.getElementById('reloadBlocklistBtn');
  const container = document.getElementById('blocklistContainer');
  const searchInput = document.getElementById('blocklistSearch');
  const dateInput = document.getElementById('blocklistDateFilter');
  const resetBtn = document.getElementById('blocklistResetBtn');

  let blocklistData = [];

  if (!openBtn || !overlay || !closeBtn) {
    console.warn("Overlay-Elemente fehlen.");
    return;
  }

  openBtn.addEventListener('click', () => {
    console.log("Open overlay...");
    overlay.classList.remove('hidden');
    loadBlocklist();
  });

  closeBtn.addEventListener('click', () => {
    overlay.classList.add('hidden');
  });

  reloadBtn?.addEventListener('click', loadBlocklist);

  searchInput?.addEventListener('input', () => {
    const filterValue = searchInput.value.trim();
    renderBlocklist(blocklistData, filterValue);
  });

  dateInput?.addEventListener('input', () => {
    renderBlocklist(blocklistData, searchInput?.value.trim() || '');
  });

  resetBtn?.addEventListener('click', () => {
    if (searchInput) searchInput.value = '';
    if (dateInput) dateInput.value = '';
    renderBlocklist(blocklistData);
  });

  function loadBlocklist() {
    container.textContent = 'Loading blocklist...';
    fetch('includes/get-blocklist.php', { cache: 'no-store' })
      .then(res => {
        if (!res.ok) throw new Error('Failed to load blocklist');
        return res.json();
      })
      .then(data => {
        blocklistData = data;
        renderBlocklist(data);
      })
      .catch(err => {
        container.textContent = 'Error loading blocklist: ' + err.message;
      });
  }

  function renderBlocklist(data, filter = '') {
    if (!Array.isArray(data) || data.length === 0) {
      container.textContent = 'Blocklist is empty.';
      return;
    }

    const activeEntries = data.filter(entry => entry.active !== false);
    const term = filter.toLowerCase();
    const selectedDate = dateInput?.value;

    const filteredData = activeEntries.filter(entry => {
      const ipMatch = entry.ip.toLowerCase().includes(term);
      const jailMatch = entry.jail && entry.jail.toLowerCase().includes(term);

      let dateMatch = true;
      if (selectedDate && entry.timestamp) {
        const entryDate = new Date(entry.timestamp).toISOString().split('T')[0];
        dateMatch = entryDate === selectedDate;
      }

      return (ipMatch || jailMatch) && dateMatch;
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
        <button data-ip="${entry.ip}">Unblock</button>
      `;

      const btn = div.querySelector('button');
      btn.addEventListener('click', () => unblockIp(entry.ip));
      container.appendChild(div);
    });
  }

  function unblockIp(ip) {
    // if (!confirm(`Unblock IP ${ip}?`)) return;

    fetch('includes/actions/action_unban-ip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ ip })
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
