async function fetchAndDisplayLogStats() {
  const bansElement = document.getElementById('fail2ban-bans');
  const unbansElement = document.getElementById('fail2ban-unbans');
  const totalElement = document.getElementById('fail2ban-total');

  try {
    const response = await fetch('includes/fail2ban-logstats.php');
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    const statsData = await response.json();

    bansElement.textContent = `${statsData.ban_count} with ${statsData.ban_unique_ips} unique IPs`;
    unbansElement.textContent = `${statsData.unban_count} with ${statsData.unban_unique_ips} unique IPs`;
    totalElement.textContent = `${statsData.total_events} events with ${statsData.total_unique_ips} unique IPs`;
  } catch (err) {
    bansElement.textContent = '--';
    unbansElement.textContent = '--';
    totalElement.textContent = '--';
    console.error('Error loading Fail2Ban stats:', err);
  }
}

document.addEventListener('DOMContentLoaded', fetchAndDisplayLogStats);
