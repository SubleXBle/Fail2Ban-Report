#!/bin/bash
# fail2ban_log2json.sh
set -euo pipefail

# === Load Configuration ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/config.env"

# === Local Variables ===
LOGFILE="/var/log/fail2ban.log"
TODAY=$(date +"%Y-%m-%d")
TODAY_SHORT=$(date +"%Y%m%d")
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$TODAY_SHORT.json"
mkdir -p "$OUTPUT_JSON_DIR"

echo "[" > "$OUTPUT_JSON_FILE"

# Grep all relevant Events
grep -E "(Ban|Unban)" "$LOGFILE" | awk -v today="$TODAY" '
{
    timestamp = $1 " " $2;
    if (index(timestamp, today) != 1) next;

    action = "";
    ip = "";
    if ($0 ~ /Increase Ban/) {
        action = "Increase Ban";
        match($0, /Increase Ban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Ban/) {
        action = "Ban";
        match($0, /Ban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Unban/) {
        action = "Unban";
        match($0, /Unban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    }

    # Extract jail from first non-numeric bracketed section
    text = $0;
    c = 0;
    delete arr;
    while (match(text, /\[[^]]+\]/)) {
        content = substr(text, RSTART+1, RLENGTH-2);
        c++;
        arr[c] = content;
        text = substr(text, RSTART + RLENGTH);
    }

    jail = "unknown";
    for(i=1; i<=c; i++) {
        if (arr[i] !~ /^[0-9]+$/) {
            jail = arr[i];
            break;
        }
    }

    if (ip != "") {
        printf "  {\n    \"timestamp\": \"%s\",\n    \"action\": \"%s\",\n    \"ip\": \"%s\",\n    \"jail\": \"%s\"\n  },\n", timestamp, action, ip, jail;
    }
}
' >> "$OUTPUT_JSON_FILE"

if [ -s "$OUTPUT_JSON_FILE" ]; then
    sed -i '$ s/},/}/' "$OUTPUT_JSON_FILE"
fi
echo "]" >> "$OUTPUT_JSON_FILE"
echo "✅ JSON created: $OUTPUT_JSON_FILE"

# === Upload JSON to Server Securely ===
upload_file() {
    local file=$1
    echo "🔄 Uploading $file ..."

    # Prepare secure configuration file for curl (Fix against process credentials leak)
    local authfile
    authfile=$(mktemp)
    chmod 600 "$authfile"

    # Add auth fields and file payload securely into the config file instead of passing via argv
    cat << EOF > "$authfile"
form="username=${CLIENT_USER}"
form="password=${CLIENT_PASS}"
form="uuid=${CLIENT_UUID}"
form="file=@${file}"
EOF

    # Execute curl request securely using the configuration file
    response=$(curl -s -w "\n%{http_code}" -X POST "$ENDPOINT_URL" --config "$authfile" || true)

    # Immediately wipe and remove the temporary authentication file to safeguard credentials
    rm -f "$authfile"

    # Parse output response
    http_code=$(tail -n1 <<< "$response")
    body=$(sed '$d' <<< "$response")

    if [ "$http_code" -eq 0 ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') ❌ Connection failed to $ENDPOINT_URL" | tee -a "$CLIENT_LOG"
        return 1
    fi

    echo "$(date '+%Y-%m-%d %H:%M:%S') HTTP Status: $http_code" | tee -a "$CLIENT_LOG"
    echo "$(date '+%Y-%m-%d %H:%M:%S') Response Body: $body" | tee -a "$CLIENT_LOG"

    if [ "$http_code" -ne 200 ]; then
        echo "$(date '+%Y-%m-%d %H:%M:%S') ❌ Upload failed (HTTP $http_code)" | tee -a "$CLIENT_LOG"
        return 1
    fi

    success=$(echo "$body" | jq -r '.success // empty')
    if [ "$success" != "true" ]; then
        message=$(echo "$body" | jq -r '.message // empty')
        echo "$(date '+%Y-%m-%d %H:%M:%S') ❌ Endpoint rejected the file: $message" | tee -a "$CLIENT_LOG"
        return 1
    fi

    echo "$(date '+%Y-%m-%d %H:%M:%S') ✅ Upload succeeded for $file" | tee -a "$CLIENT_LOG"
}

upload_file "$OUTPUT_JSON_FILE"
echo "✅ Upload completed."
