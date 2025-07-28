document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.querySelector('#resultTable tbody');
  tbody.addEventListener('click', e => {
    if (e.target.classList.contains('action-btn')) {
      const ip = e.target.dataset.ip;
      collectAndExecuteActions(ip); // <- zentrale Logik ausgelagert
    }
  });
});
