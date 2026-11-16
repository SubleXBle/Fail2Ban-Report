async function fetchAndDisplayLogStats() {
  const bansElement = document.getElementById('fail2ban-bans');
  const unbansElement = document.getElementById('fail2ban-unbans');
  const totalElement = document.getElementById('fail2ban-total');

  const yesterdayElement = document.getElementById('fail2ban-yesterday');
  const last7Element = document.getElementById('fail2ban-last7');
  const last30Element = document.getElementById('fail2ban-last30');

  // Container für Bans pro Jail und Top 3
  const perJailContainer = document.getElementById('fail2ban-bans-per-jail');
  const top3Container = document.getElementById('fail2ban-top3-jails');

  try {
    const response = await fetch('includes/fail2ban-logstats.php');
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    const statsData = await response.json();

    // Heute
    bansElement.textContent = `${statsData.ban_count}`;
    bansElement.title = `${statsData.ban_count} Bans with ${statsData.ban_unique_ips} unique IPs`;
    unbansElement.textContent = `${statsData.unban_count}`;
    unbansElement.title = `${statsData.unban_count} Unbans with ${statsData.unban_unique_ips} unique IPs`;
    totalElement.textContent = `${statsData.total_events}`;
    totalElement.title = `${statsData.total_events} events with ${statsData.total_unique_ips} unique IPs`;

    // Aggregiert
    if (statsData.aggregated) {
      const aggr = statsData.aggregated;
      yesterdayElement.textContent = `${aggr.yesterday.total_events}  🚫${aggr.yesterday.ban_count} 🟢${aggr.yesterday.unban_count} `;
      yesterdayElement.title = `🚫 ${aggr.yesterday.ban_count} Bans, 🟢 ${aggr.yesterday.unban_count} Unbans, ${aggr.yesterday.total_unique_ips} unique IPs`;

      last7Element.textContent = `${aggr.last_7_days.total_events}  🚫${aggr.last_7_days.ban_count} 🟢${aggr.last_7_days.unban_count} `;
      last7Element.title = `🚫 ${aggr.last_7_days.ban_count} Bans, 🟢 ${aggr.last_7_days.unban_count} Unbans, ${aggr.last_7_days.total_unique_ips} unique IPs`;

      last30Element.textContent = `${aggr.last_30_days.total_events}  🚫${aggr.last_30_days.ban_count} 🟢${aggr.last_30_days.unban_count} `;
      last30Element.title = `🚫 ${aggr.last_30_days.ban_count} Bans, 🟢 ${aggr.last_30_days.unban_count} Unbans, ${aggr.last_30_days.total_unique_ips} unique IPs`;
    }

    // Bans pro Jail anzeigen
    if (statsData.ban_count_per_jail && perJailContainer) {
      const entries = Object.entries(statsData.ban_count_per_jail);

      if (entries.length === 0) {
        perJailContainer.textContent = 'No bans recorded per jail.';
      } else {
        perJailContainer.textContent = entries
          .map(([jail, count]) => `${jail}: ${count}`)
          .join(' | ');
      }
    }

    // Top 3 Jails mit meisten Bans anzeigen (XSS-safe)
    if (statsData.ban_count_per_jail && top3Container) {
      const top3 = Object.entries(statsData.ban_count_per_jail)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3);

      top3Container.textContent = '';

      if (top3.length === 0) {
        top3Container.textContent = 'No top jails available.';
      } else {
        top3.forEach(([jail, count]) => {
          const li = document.createElement('li');
          li.textContent = `${jail}: ${count}`;
          top3Container.appendChild(li);
        });
      }
    }

  } catch (err) {
    bansElement.textContent = '--';
    unbansElement.textContent = '--';
    totalElement.textContent = '--';
    if (yesterdayElement) yesterdayElement.textContent = '--';
    if (last7Element) last7Element.textContent = '--';
    if (last30Element) last30Element.textContent = '--';
    if (perJailContainer) perJailContainer.textContent = '--';
    if (top3Container) top3Container.textContent = '--';
    console.error('Error loading Fail2Ban stats:', err);
  }
}

document.addEventListener('DOMContentLoaded', fetchAndDisplayLogStats);
