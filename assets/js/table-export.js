function copyFilteredToClipboard() {
    if (!Array.isArray(allData) || allData.length === 0) {
        alert('⚠ No data to copy!');
        return;
    }

    const selectedDate = getSelectedDate();
    const actionFilter = document.getElementById('actionFilter').value;
    const jailFilter = document.getElementById('jailFilter').value;
    const ipFilter = document.getElementById('ipFilter').value.trim();
    const markFilter = document.getElementById('markFilter').value;

    // === Filter Schritt 1 ===
    let filtered = allData.filter(entry => {
        const entryDate = entry.timestamp ? entry.timestamp.substring(0, 10) : '';
        return (!selectedDate || entryDate === selectedDate) &&
               (!actionFilter || entry.action === actionFilter) &&
               (!jailFilter || entry.jail === jailFilter) &&
               (!ipFilter || entry.ip.includes(ipFilter));
    });

    // === Marker-Berechnung Schritt 2 ===
    const eventCounts = {};
    const ipJails = {};
    filtered.forEach(e => {
        const key = e.ip + '|' + e.action;
        eventCounts[key] = (eventCounts[key] || 0) + 1;
        if (!ipJails[e.ip]) ipJails[e.ip] = new Set();
        ipJails[e.ip].add(e.jail);
    });

    filtered.forEach(e => {
        let marker = '';
        if (eventCounts[e.ip + '|' + e.action] > 1) marker += '🟡';
        if (ipJails[e.ip].size > 1) marker += '🔴';
        if (!marker) marker = '⚪';
        e.marker = marker;
    });

    // === Markerfilter Schritt 3 ===
    filtered = filtered.filter(e => {
        if (!markFilter) return true;
        if (markFilter === 'yellow') return e.marker.includes('🟡') && !e.marker.includes('🔴');
        if (markFilter === 'red') return e.marker.includes('🔴') && !e.marker.includes('🟡');
        if (markFilter === 'yellowred') return e.marker.includes('🟡') && e.marker.includes('🔴');
        if (markFilter === 'none') return e.marker === '⚪';
        return true;
    });

    if (filtered.length === 0) {
        alert('⚠ No data to copy!');
        return;
    }

    // === Sortierung Schritt 4 ===
    const sorted = [...filtered].sort((a, b) => {
        const { column, direction } = currentSort;
        let valA = a[column] || '';
        let valB = b[column] || '';

        if (column === 'timestamp') {
            valA = Date.parse(valA.replace(' ', 'T').replace(',', '.'));
            valB = Date.parse(valB.replace(' ', 'T').replace(',', '.'));
        } else {
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
        }

        if (valA < valB) return direction === 'asc' ? -1 : 1;
        if (valA > valB) return direction === 'asc' ? 1 : -1;
        return 0;
    });

    // === In Text umwandeln Schritt 5 ===
    let text = '';
    sorted.forEach(entry => {
        text += [
            entry.timestamp,
            entry.action,
            entry.marker,
            entry.ip,
            entry.jail
        ].join('\t') + '\n';
    });

    // === Clipboard Schritt 6 ===
    navigator.clipboard.writeText(text).then(() => {
        alert(`✅ ${sorted.length} rows copied to clipboard!`);
    }).catch(err => {
        console.error('Clipboard error:', err);
        alert('❌ Failed to copy data.');
    });
}
