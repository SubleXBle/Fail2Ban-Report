#!/bin/bash

set -euo pipefail

# === Configuration ===
SERVER_URL="https://yourserver.com/endpoint/download.php"
USERNAME="ClientServer1"
PASSWORD="deinPasswort"
UUID="deinUUID"
DOWNLOAD_DIR="/opt/Fail2Ban-Report/archive/$USERNAME/blocklists"
LOGFILE="/var/log/Fail2Ban-Report.log"
LOGGING=true

# === Logging function ===
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

mkdir -p "$DOWNLOAD_DIR"

# === 1) Liste der verfügbaren Updates vom Server holen ===
RESPONSE=$(curl -s -X POST "$SERVER_URL" \
  -d "username=$USERNAME" \
  -d "password=$PASSWORD" \
  -d "uuid=$UUID" \
  -d "check=true") # optionaler Parameter, falls serverseitig Update prüfen

# === 2) Updates auswerten ===
UPDATES=$(echo "$RESPONSE" | jq -r '.updates[]?')
if [ -z "$UPDATES" ]; then
  log "No blocklist updates available."
  echo "ℹ️ No updates available."
  exit 0
fi

log "Updates available: $(echo "$UPDATES" | wc -l) blocklist(s)."

# === 3) Jede Blocklist herunterladen ===
for FILE in $UPDATES; do
  log "Downloading blocklist: $FILE"
  curl -s -X GET "$SERVER_URL?file=$FILE" \
       -d "username=$USERNAME" \
       -d "password=$PASSWORD" \
       -d "uuid=$UUID" \
       -o "$DOWNLOAD_DIR/$FILE"

  if [ $? -eq 0 ]; then
    log "Successfully downloaded $FILE"
    echo "✅ $FILE downloaded."
  else
    log "Failed to download $FILE"
    echo "❌ Failed to download $FILE"
  fi
done

echo "Blocklist download completed."
