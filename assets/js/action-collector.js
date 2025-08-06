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
  let type;

  // check fo type
  if (data.type) {
    type = data.type; // from backend
  } else {
    // fallback:
    type = data.success ? 'success' : 'error';
  }

  showNotification(prefix + message, type);
})


    .catch(err => {
      const errorMsg = `[${action.toUpperCase()}] Error processing IP ${ip}: ${err}`;
      showNotification(errorMsg, "error");
      console.error(errorMsg);
    });
  });
}

/**
 * Shows a toast-style notification in the page.
 *
 * @param {string} message - The text to show in the notification.
 * @param {string} type - One of "success", "error", or "info" (defaults to "info").
 */
function showNotification(message, type = "info") {
  const container = document.getElementById('notification-container');
  if (!container) return;

  const note = document.createElement('div');
  note.className = 'notification ' + type; // CSS Klassen wie notification success/error/info

  note.innerText = message;
  container.appendChild(note);

  // Remove notification after 5 seconds
  setTimeout(() => {
    note.remove();
  }, 5000);
}
