#!/bin/bash
# syncback.sh
set -euo pipefail

# === Load Configuration ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/config.env"

# === Logging function ===
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$CLIENT_LOG"
}

# === Collect all blocklist files ===
mapfile -t files < <(find "$BLOCKLIST_DIR" -maxdepth 1 -type f -name '*.blocklist.json')

if [ "${#files[@]}" -eq 0 ]; then
    log "No blocklist files found in $BLOCKLIST_DIR"
    exit 0
fi

log "Uploading ${#files[@]} blocklist file(s) to backsync.php ..."

# === Prepare secure configuration file for curl (Fix against process credentials leak) ===
authfile=$(mktemp)
chmod 600 "$authfile"

# Add auth fields securely into the configuration file instead of passing via argv
cat << EOF > "$authfile"
form="username=${CLIENT_USER}"
form="password=${CLIENT_PASS}"
form="uuid=${CLIENT_UUID}"
EOF

# Add files dynamically to the secure curl configuration file
for f in "${files[@]}"; do
    echo "form=\"file[]=@$f\"" >> "$authfile"
done

# === Upload to server securely ===
response=$(curl -s -w "\n%{http_code}" --config "$authfile" "$BACKSYNC_URL" || true)

# Immediately wipe and remove the temporary authentication file to safeguard credentials
rm -f "$authfile"

# Parse output response
http_code=$(tail -n1 <<< "$response")
body=$(sed '$d' <<< "$response")

log "HTTP Status: $http_code"
log "Server Response: $body"

if [ "$http_code" -ne 200 ]; then
    log "❌ Upload failed (HTTP $http_code)"
    exit 1
fi

success=$(echo "$body" | jq -r '.success // empty')
if [ "$success" != "true" ]; then
    message=$(echo "$body" | jq -r '.message // empty')
    log "❌ Endpoint rejected the files: $message"
    exit 1
fi

uploaded_files=$(echo "$body" | jq -r '.files[]?')
if [ -n "$uploaded_files" ]; then
    log "✅ Successfully uploaded files: $uploaded_files"
else
    log "⚠️ No files were uploaded according to server response"
fi

log "All blocklists processed and upload attempt finished."
