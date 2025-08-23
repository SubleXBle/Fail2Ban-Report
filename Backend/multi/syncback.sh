#!/bin/bash
# backsync-upload.sh
# Upload all *.blocklist.json files to backsync.php endpoint

set -euo pipefail

# === Configuration ===
BLOCKLIST_DIR="/opt/Fail2Ban-Report/archive/blocklists"
CLIENT_USER="MyClientName"
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"
BACKSYNC_URL="https://my.server.tld/Fail2Ban-Report/endpoint/backsync.php"
CLIENT_LOG="/var/log/fail2ban-report-client.log"

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

# === Prepare curl args for multiple files ===
curl_args=()
for f in "${files[@]}"; do
    curl_args+=("-F" "file[]=@$f")
done

# Add auth fields
curl_args+=(
    "-F" "username=$CLIENT_USER"
    "-F" "password=$CLIENT_PASS"
    "-F" "uuid=$CLIENT_UUID"
)

log "Uploading ${#files[@]} blocklist file(s) to backsync.php ..."

# === Upload to server ===
response=$(curl -s -w "\n%{http_code}" "${curl_args[@]}" "$BACKSYNC_URL" || true)
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
