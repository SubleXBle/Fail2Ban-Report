#!/bin/bash
# update-check.sh – Prüft, ob Blocklist-Updates vorhanden sind, und lädt sie herunter

set -euo pipefail

# === Konfiguration ===
SERVER_URL="https://my.server.tld/Fail2Ban-Report/endpoint/update.php"
CLIENT_USER="MyClientName"        # Username = Servername auf WebUI
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"
DOWNLOAD_DIR="/var/lib/fail2ban-report/blocklists"  # Lokaler Speicherort der Blocklists
LOG_FILE="/var/log/fail2ban-report-update.log"

mkdir -p "$DOWNLOAD_DIR"

# === Hilfsfunktion: Loggen ===
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" | tee -a "$LOG_FILE"
}

# === 1) Anfrage an update.php ===
log "🔄 Prüfe auf Blocklist-Updates..."
response=$(curl -s -X POST "$SERVER_URL" \
    -F "username=$CLIENT_USER" \
    -F "password=$CLIENT_PASS" \
    -F "uuid=$CLIENT_UUID")

if [[ -z "$response" ]]; then
    log "❌ Keine Antwort vom Server!"
    exit 1
fi

# === 2) Prüfen, ob Updates vorhanden ===
updates=$(echo "$response" | jq -r '.updates[]?')
if [[ -z "$updates" ]]; then
    log "✅ Keine neuen Blocklists."
    exit 0
fi

log "✅ Gefundene Blocklists zum Download: $updates"

# === 3) Blocklists herunterladen ===
for list in $updates; do
    # URL für die Blocklist-Datei
    file_url="${SERVER_URL%/*}/$CLIENT_USER/blocklists/$list"
    target_file="$DOWNLOAD_DIR/$list"

    if curl -s -f -o "$target_file" "$file_url"; then
        log "✅ $list erfolgreich heruntergeladen nach $target_file"
    else
        log "❌ Fehler beim Herunterladen von $list"
    fi
done

log "🔄 Blocklist-Update abgeschlossen."
