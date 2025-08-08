document.addEventListener("DOMContentLoaded", function () {
  fetchWarnings();
  setInterval(fetchWarnings, 10000); // Reload every 10 seconds

  function fetchWarnings() {
    fetch("includes/warnings.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.status !== "ok" || !data.enabled) {
          return;
        }

        updateStatus(
          "warning",
          data.warning.total_events,
          data.warning.total_unique_ips,
          data.warning.jails,
          data.warning.jail_names || []
        );
        updateStatus(
          "critical",
          data.critical.total_events,
          data.critical.total_unique_ips,
          data.critical.jails,
          data.critical.jail_names || []
        );
      })
      .catch((error) => {
        console.error("Failed to load warning data:", error);
      });
  }

  function updateStatus(type, totalEvents, totalIPs, jailStats, jailNames) {
    const dot = document.getElementById(`${type}-dot`);
    const label = document.getElementById(`${type}-label`);

    if (!dot || !label) return;

    if (totalEvents > 0) {
      dot.classList.remove("disabled");

      if (jailNames.length === 1) {
        // Only one jail, short line
        const j = jailStats[jailNames[0]];
        label.textContent = `${jailNames[0]} | ${j.events} | ${j.unique_ips}`;
      } else if (jailNames.length > 1) {
        // Multiple jails, list with lines
        const lines = jailNames.map(jail => {
          const j = jailStats[jail];
          return `${jail} | ${j.events} | ${j.unique_ips}`;
        });
        label.innerHTML = lines.join('<br>');
      } else {
        // No jail name (fallback)
        label.textContent = `${totalIPs}/${totalEvents} ${capitalize(type)}${totalIPs !== 1 ? "s" : ""}`;
      }
    } else {
      dot.classList.add("disabled");
      label.textContent = "none";
    }
  }

  function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
  }
});
