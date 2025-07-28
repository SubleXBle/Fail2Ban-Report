document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('blocklistOverlay');
  const openBtn = document.getElementById('openBlocklistBtn');
  const closeBtn = document.getElementById('closeOverlayBtn');
  const reloadBtn = document.getElementById('reloadBlocklistBtn');
  const container = document.getElementById('blocklistContainer');
  const searchInput = document.getElementById('blocklistSearch');

  let blocklistData = [];

  // Show overlay and load blocklist
  openBtn.addEventListener('click', () => {
    overlay.classList.remove('hidden');
    loadBlocklist();
  });

  // Close overlay
  closeBtn.addEventListener('click', () => {
    overlay.classList.add('hidden');
  });

  // Reload blocklist manually
  reloadBtn.addEventListener('click', loadBlocklist);

  // Filter blocklist while typing
  searchInput.addEventListener('input', () => {
    const filterValue = searchInput.value.trim();
    renderBlocklist(blocklistData, filterValue);
  });

  // Load blocklist from JSON file
  function loadBlocklist() {
    container.textContent = 'Loading blocklist...';

    fetch('/archive/blocklist.json', { cache: 'no-store' })
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

  // Render blocklist entries with optional filtering
  function renderBlocklist(data, filter = '') {
    if (!Array.isArray(data) || data.length === 0) {
      container.textContent = 'Blocklist is empty.';
      return;
    }

    const activeEntries = data.filter(entry => entry.active !== false); // default to true if field missing

    const filteredData = activeEntries.filter(entry => {
      const term = filter.toLowerCase();
      return (
        entry.ip.toLowerCase().includes(term) ||
        (entry.jail && entry.jail.toLowerCase().includes(term))
      );
    });

    if (filteredData.length === 0) {
      container.textContent = 'No entries match your search.';
      return;
    }

    container.innerHTML = ''; // Clear container

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

  // Unblock an IP via POST request
  function unblockIp(ip) {
    if (!confirm(`Unblock IP ${ip}?`)) return;

    fetch('/includes/actions/action_unban-ip.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ ip })
    })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) loadBlocklist(); // Refresh list
    })
    .catch(err => {
      alert('Error unblocking IP: ' + err.message);
    });
  }
});
