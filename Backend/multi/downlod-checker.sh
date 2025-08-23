#!/bin/bash

set -euo pipefail

# --- Configuration ---
SERVER_URL="https://deinserver.tld/endpoint/download.php"
USERNAME="deinusername"
PASSWORD="deinpasswort"
UUID="dein-uuid"
DEST_DIR="/path/to/downloaded/blocklists"
LOGFILE="/var/log/Fail2Ban-Report-download.log"
LOGGING=true

# --- Logging function ---
log() {
    if [ "$LOGGING" = true ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOGFILE"
    fi
}

mkdir -p "$DEST_DIR"

# --- Step 1: Liste aller Blocklists prüfen (via download.php) ---
log "Checking available blocklists for $USERNAME..."

RESPONSE=$(curl -s -X POST "$SERVER_URL" \
    -d "username=$USERNAME" \
    -d "password=$PASSWORD" \
    -d "uuid=$UUID")

# Prüfen ob JSON
if ! echo "$RESPONSE" | jq . >/dev/null 2>&1; then
    log "ERROR: Server response is not valid JSON:"
    log "$RESPONSE"
    exit 1
fi

# Prüfen auf Fehler
SUCCESS=$(echo "$RESPONSE" | jq -r '.success')
if [ "$SUCCESS" != "true" ]; then
    MESSAGE=$(echo "$RESPONSE" | jq -r '.message')
    log "ERROR from server: $MESSAGE"
    exit 1
fi

# --- Step 2: Liste der verfügbaren Blocklists herunterladen ---
FILES=$(echo "$RESPONSE" | jq -r '.updates[]?')
if [ -z "$FILES" ]; then
    log "No blocklists available for download."
    exit 0
fi

for FILE in $FILES; do
    log "Downloading blocklist: $FILE"

    curl -s -X POST "$SERVER_URL?file=$FILE" \
        -d "username=$USERNAME" \
        -d "password=$PASSWORD" \
        -d "uuid=$UUID" \
        -o "$DEST_DIR/$FILE"

    if [ $? -eq 0 ]; then
        log "Blocklist $FILE downloaded successfully."
    else
        log "ERROR downloading $FILE"
    fi
done

log "All available blocklists processed."
exit 0
