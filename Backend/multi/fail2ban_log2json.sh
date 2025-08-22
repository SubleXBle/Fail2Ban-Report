#!/bin/bash
# fail2ban_log2json.sh
# will send the logfile to the server when done creating

set -euo pipefail

# === Configuration ===
LOGFILE="/var/log/fail2ban.log"
OUTPUT_JSON_DIR="/var/www/Fail2Ban-Report/archive"
CLIENT_USER="MyClientName"
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"
ENDPOINT_URL="https://my.server.tld/Fail2Ban-Report/endpoint/index.php"
CLIENT_LOG="/var/log/fail2ban-report-client.log"

# === create JSON  ===
TODAY=$(date +"%Y-%m-%d")
TODAY_SHORT=$(date +"%Y%m%d")
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$TODAY_SHORT.json"
mkdir -p "$OUTPUT_JSON_DIR"

echo "[" > "$OUTPUT_JSON_FILE"

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

# === Upload JSON to Server ===
upload_file() {
    local file=$1
    echo "🔄 Uploading $file ..."

    response=$(curl -s -w "\n%{http_code}" -X POST "$ENDPOINT_URL" \
        -F "username=$CLIENT_USER" \
        -F "password=$CLIENT_PASS" \
        -F "uuid=$CLIENT_UUID" \
        -F "file=@$file" || true)

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

# Upload JSON
upload_file "$OUTPUT_JSON_FILE"

echo "✅ Upload completed."
