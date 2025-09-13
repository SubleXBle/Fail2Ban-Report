document.addEventListener('DOMContentLoaded', () => {
    async function updateUFWBlocks() {
        const div = document.getElementById('ufw-blocks-info');
        if (!div) return;

        try {
            const response = await fetch('includes/ufw-report.php');
            if (!response.ok) throw new Error(`Network response was not ok`);
            const data = await response.json();

            // Gesamt
            let output = `Total Matches: ${data.total}`;

            // Per IP
            if (data.per_ip && Object.keys(data.per_ip).length > 0) {
                const ipInfo = Object.entries(data.per_ip).map(([ip, info]) => {
                    return `${info.blocklist}: ${ip} (${info.count})`;
                });
                output += ' | ' + ipInfo.join(' | ');
            }

            div.textContent = output;
        } catch (err) {
            console.error('Error fetching UFW blocklist data:', err);
            div.textContent = '⚠ Error loading UFW blocklist info';
        }
    }

    updateUFWBlocks();
});
