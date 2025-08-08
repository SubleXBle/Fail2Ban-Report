document.addEventListener("DOMContentLoaded", () => {
  fetchBlocklistStats();
  setInterval(fetchBlocklistStats, 10000); // optional: refresh every 60 seconds
});

function fetchBlocklistStats() {
  fetch('includes/blocklist-stats.php')
    .then(response => {
      if (!response.ok) {
        throw new Error("Failed to fetch blocklist stats");
      }
      return response.json();
    })
    .then(data => renderBlocklistStats(data))
    .catch(error => console.error("Error loading blocklist stats:", error));
}

function renderBlocklistStats(stats) {
  const container = document.getElementById("blocklist-stats-container");
  if (!container) return;

  container.innerHTML = ''; // Clear previous content
  const sortedJails = Object.keys(stats).sort();

  sortedJails.forEach(jail => {
    const { active, pending } = stats[jail];

    const div = document.createElement("div");
    div.innerHTML = `🔒 ${jail}: 🟢 ${active} / 🟡${pending}`;
    container.appendChild(div);
  });
}
