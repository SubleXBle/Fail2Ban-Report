function collectAndExecuteActions(ip, jail = '') {
  const selectedActions = Array.from(document.querySelectorAll('input[name="actions"]:checked'))
    .map(input => input.value);

  if (selectedActions.length === 0) {
    alert("Please select at least one action.");
    return;
  }

  selectedActions.forEach(action => {
    const scriptUrl = `includes/actions/action_${action}-ip.php`;

    const params = { ip };
    if (jail) params.jail = jail;

    fetch(scriptUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams(params)
    })
    .then(res => res.text())
    .then(responseText => {
      showNotification(`[${action.toUpperCase()}] ${responseText}`);
    })
    .catch(err => {
      showNotification(`[${action.toUpperCase()}] Error processing IP ${ip}: ${err}`, true);
      console.error(`Error during action [${action}] for IP ${ip}:`, err);
    });
  });
}

// 🔔 Toast Notification Function
function showNotification(message, isError = false) {
  const container = document.getElementById('notification-container');
  if (!container) return;

  const note = document.createElement('div');
  note.className = 'notification';
  note.style.backgroundColor = isError ? '#722' : '#a8d5ba'; // red for errors, soft green for success
  note.style.color = isError ? '#f8f8f8' : '#2e4d32'; // light text on error, dark text on green

  note.innerText = message;
  container.appendChild(note);

  // Automatically remove after 5 seconds
  setTimeout(() => {
    note.remove();
  }, 5000);
}
