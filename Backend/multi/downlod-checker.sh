mkdir -p "$DOWNLOAD_DIR"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOGFILE"
}

# --- 1) Liste der zu aktualisierenden Blocklists vom Server holen ---
log "Checking for blocklist updates..."
UPDATES_JSON=$(curl -s -X POST -d "username=$USERNAME&password=$PASSWORD&uuid=$UUID" \
    "https://deinserver.tld/endpoint/update.php")

# Prüfen ob Updates vorhanden sind
UPDATES=$(echo "$UPDATES_JSON" | grep -Po '(?<="updates": \[)[^\]]*' | tr -d '"[] ' | tr ',' '\n')

if [ -z "$UPDATES" ]; then
    log "No blocklist updates available."
    exit 0
fi

log "Updates available: $(echo "$UPDATES" | wc -l) blocklist(s)."

# --- 2) Jede Blocklist herunterladen ---
for BLOCKLIST in $UPDATES; do
    OUTPUT_FILE="$DOWNLOAD_DIR/$BLOCKLIST"
    log "Downloading $BLOCKLIST..."
    curl -s -X POST -d "username=$USERNAME&password=$PASSWORD&uuid=$UUID" \
         "$SERVER_URL?file=$BLOCKLIST" -o "$OUTPUT_FILE"

    if [ $? -eq 0 ] && [ -s "$OUTPUT_FILE" ]; then
        log "$BLOCKLIST downloaded successfully to $OUTPUT_FILE"
    else
        log "Failed to download $BLOCKLIST"
    fi
done

log "All available blocklists downloaded."
