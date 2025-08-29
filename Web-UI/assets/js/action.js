document.addEventListener('DOMContentLoaded', () => {
  function getSelectedIpsAndJails() {
    const selectedCheckboxes = Array.from(document.querySelectorAll('.ip-select:checked'));
    const ips = selectedCheckboxes.map(cb => cb.dataset.ip);
    const jails = selectedCheckboxes.map(cb => cb.dataset.jail);
    return { ips, jails };
  }

  document.getElementById('banSelectedBtn').addEventListener('click', () => {
    const { ips, jails } = getSelectedIpsAndJails();
    if (ips.length === 0) {
      showNotification('Please select at least one IP to ban.', 'info');
      return;
    }
    collectAndExecuteActions(ips, 'ban', jails);
  });

  document.getElementById('reportSelectedBtn').addEventListener('click', () => {
    const { ips, jails } = getSelectedIpsAndJails();
    if (ips.length === 0) {
      showNotification('Please select at least one IP to report.', 'info');
      return;
    }
    collectAndExecuteActions(ips, 'report', jails);
  });
});
