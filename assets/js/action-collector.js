/**
 * Collects selected IPs and sends the chosen action request(s) to backend PHP action handlers.
 *
 * @param {string|string[]} ips - Single IP or array of IPs.
 * @param {string} action - Action, e.g. 'ban' or 'report'.
 * @param {string|string[]} [jails] - Optional jail or array of jails matching ips.
 */
function collectAndExecuteActions(ips, action, jails = []) {
  if (!Array.isArray(ips)) {
    ips = [ips];
  }
  if (!Array.isArray(jails)) {
    jails = [jails];
  }

  if (ips.length === 0) {
    showNotification("Please select at least one IP.", "info");
    return;
  }

  ips.forEach((ip, index) => {
    const jail = jails[index] || '';
    const scriptUrl = `includes/actions/action_${action}-ip.php`;

    const params = { ip };
    if (jail) params.jail = jail;

    fetch(scriptUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(params)
    })
    .then(res => res.json())
    .then(data => {
      const prefix = `[${action.toUpperCase()}] `;
      const message = data.message || 'No message returned.';
      const type = data.type || (data.success ? 'success' : 'error');
      // when Notification - display 10 Seconds
      const duration = (action === 'report') ? 10000 : 5000;
      showNotification(prefix + message, type, duration);
    })
    .catch(err => {
      const errorMsg = `[${action.toUpperCase()}] Error processing IP ${ip}: ${err}`;
      showNotification(errorMsg, "error");
      console.error(errorMsg);
    });
  });
}
