document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#resultTable tbody');
  tbody.addEventListener('click', e => {
    if (e.target.classList.contains('action-btn')) {
      const ip = e.target.dataset.ip;
      const jail = e.target.dataset.jail || '';
      collectAndExecuteActions(ip, jail);
    }
  });
});
