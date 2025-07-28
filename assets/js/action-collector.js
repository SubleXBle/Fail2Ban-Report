function collectAndExecuteActions(ip) {
  const selectedActions = Array.from(document.querySelectorAll('input[name="actions"]:checked'))
    .map(input => input.value);

  if (selectedActions.length === 0) {
    alert("Bitte wähle mindestens eine Aktion aus.");
    return;
  }

  selectedActions.forEach(action => {
    const scriptUrl = `/includes/actions/action_${action}-ip.php`;

    fetch(scriptUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ ip })
    })
    .then(res => res.text())
    .then(responseText => {
      showNotification(`[${action.toUpperCase()}] ${responseText}`);
    })
    .catch(err => {
      showNotification(`[${action.toUpperCase()}] Fehler bei IP ${ip}: ${err}`, true);
      console.error(`Fehler bei Aktion [${action}] für ${ip}:`, err);
    });
  });
}

// 🔔 Toast-Funktion
function showNotification(message, isError = false) {
  const container = document.getElementById('notification-container');
  if (!container) return;

  const note = document.createElement('div');
  note.className = 'notification';
  if (isError) {
    note.style.backgroundColor = '#722';
    note.style.color = '#a8d5ba';
  }
  note.innerText = message;
  container.appendChild(note);

  // Remove Element after 5 Seconds
  setTimeout(() => {
    note.remove();
  }, 5000);
}
