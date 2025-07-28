function collectAndExecuteActions(ip) {
  const selectedActions = Array.from(document.querySelectorAll('input[name="actions"]:checked'))
    .map(input => input.value);

  if (selectedActions.length === 0) {
    alert("Bitte wähle mindestens eine Aktion aus.");
    return;
  }

  selectedActions.forEach(action => {
    const scriptUrl = `/actions/action_${action}-ip.php`;

    fetch(scriptUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({ ip })
    })
    .then(res => res.text())
    .then(responseText => {
      console.log(`[${action}] für ${ip} ausgeführt:`, responseText);
      // Optional: visuelles Feedback z.B. Farbe ändern, Meldung anzeigen etc.
    })
    .catch(err => {
      console.error(`Fehler bei Aktion [${action}] für ${ip}:`, err);
    });
  });
}
