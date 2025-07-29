// public/js/action-collector.js

/**
 * Collects selected actions for an IP and sends requests to backend PHP action handlers.
 * Assumes each action corresponds to an action_{name}-ip.php endpoint in includes/actions/.
 *
 * @param {string} ip - The IP address to act upon.
 * @param {string} jail - Optional jail/context name.
 */
function collectAndExecuteActions(ip, jail = '') {
  const selectedActions = Array.from(document.querySelectorAll('input[name="actions"]:checked'))
    .map(input => input.value);

  if (selectedActions.length === 0) {
    alert("Please select at least one action.");
    showNotification("Please select at least one action.", "info");
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
    .then(res => res.json())
    .then(data => {
      const prefix = `[${action.toUpperCase()}] `;
      const message = data.message || 'No message returned.';
      const isError = !data.success;
      showNotification(prefix + message, isError);
    })
    .catch(err => {
      const errorMsg = `[${action.toUpperCase()}] Error processing IP ${ip}: ${err}`;
      showNotification(errorMsg, true);
      console.error(errorMsg);
    });
  });
}

/**
 * Shows a toast-style notification in the page.
 *
 * @param {string} message - The text to show in the notification.
 * @param {boolean} isError - Whether this is an error (red) or success/info (green).
 */
function showNotification(message, isError = false) {
  const container = document.getElementById('notification-container');
  if (!container) return;

  const note = document.createElement('div');
  note.className = 'notification';
  note.style.backgroundColor = isError ? '#722' : '#a8d5ba';  // red for errors, soft green for success
  note.style.color = isError ? '#f8f8f8' : '#2e4d32';          // light text on red, dark text on green
  note.style.padding = '0.5em 1em';
  note.style.marginBottom = '0.5em';
  note.style.borderRadius = '4px';
  note.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';
  note.style.fontSize = '0.95em';

  note.innerText = message;
  container.appendChild(note);

  // Remove notification after 5 seconds
  setTimeout(() => {
    note.remove();
  }, 5000);
}
