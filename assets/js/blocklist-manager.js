document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.getElementById('blocklistOverlay');
  const openBtn = document.getElementById('openBlocklistBtn');
  const closeBtn = document.getElementById('closeOverlayBtn');
  const reloadBtn = document.getElementById('reloadBlocklistBtn');
  const container = document.getElementById('blocklistContainer');

  // Show overlay
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

  // Load blocklist.json and render entries
  function loadBlocklist() {
    container.textContent = 'Loading blocklist...';

    fetch('/archive/blocklist.json', {cache: "no-store"})
      .then(res => {
        if (!res.ok) throw new Error('Failed to load blocklist');
        return res.json();
      })
      .then(data => {
        renderBlocklist(data);
      })
      .catch(err => {
        container.textContent = 'Error loading blocklist: ' + err.message;
      });
  }

  // Render blocklist entries with unblock buttons
  function renderBlocklist(data) {
    if (!Array.isArray(data) || data.length === 0) {
      container.textContent = 'Blocklist is empty.';
      return;
    }

    container.innerHTML = ''; // clear

    data.forEach(entry => {
      const div = document.createElement('div');
      div.className = 'blocklist-entry';

      div.innerHTML = `
        <span>${entry.ip} (Jail: ${entry.jail}) - Blocked at: ${new Date(entry.timestamp).toLocaleString()}</span>
        <button data-ip="${entry.ip}">Unblock</button>
      `;

      const btn = div.querySelector('button');
      btn.addEventListener('click', () => unblockIp(entry.ip));

      container.appendChild(div);
    });
  }

  // Send unblock request via AJAX
  function unblockIp(ip) {
    if (!confirm(`Unblock IP ${ip}?`)) return;

    fetch('/includes/actions/action_unban-ip.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ip})
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
