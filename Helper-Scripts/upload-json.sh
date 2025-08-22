#!/bin/bash
# upload-latest-events.sh
# Überträgt nur die neueste fail2ban-events JSON an den Endpoint

set -euo pipefail

# --- Colors ---
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
NC="\033[0m"

# --- Configuration ---
ENDPOINT="https://your-server/Fail2Ban-Report/endpoint/index.php"
USERNAME="testclient"
PASSWORD="testpassword"
UUID="1234-5678-uuid"
JSON_DIR="/var/www/Fail2Ban-Report/archive"  # Ort der JSON-Dateien
CLIENT_LOG="/var/log/fail2ban-report-client.log"

# --- Find latest JSON file ---
LATEST_JSON=$(ls -1t "$JSON_DIR"/fail2ban-events-*.json 2>/dev/null | head -n1)
if [ -z "$LATEST_JSON" ]; then
    echo -e "${RED}❌ No JSON files found in $JSON_DIR${NC}" | tee -a "$CLIENT_LOG"
    exit 1
fi

echo -e "${YELLOW}Uploading latest JSON: $LATEST_JSON ...${NC}" | tee -a "$CLIENT_LOG"

# --- Function to POST JSON with checks ---
upload_file() {
    local file=$1

    response=$(curl -s -w "\n%{http_code}" -X POST "$ENDPOINT" \
        -F "username=$USERNAME" \
        -F "password=$PASSWORD" \
        -F "uuid=$UUID" \
        -F "file=@$file" || true)

    http_code=$(tail -n1 <<< "$response")
    body=$(sed '$d' <<< "$response")

    if [ "$http_code" -eq 0 ]; then
        echo -e "${RED}❌ Connection failed to $ENDPOINT${NC}" | tee -a "$CLIENT_LOG"
        return 1
    fi

    echo -e "HTTP Status: $http_code" | tee -a "$CLIENT_LOG"
    echo -e "Response Body: $body" | tee -a "$CLIENT_LOG"

    if [ "$http_code" -ne 200 ]; then
        echo -e "${RED}❌ Upload failed with HTTP code $http_code${NC}" | tee -a "$CLIENT_LOG"
        return 1
    fi

    success=$(echo "$body" | jq -r '.success // empty')
    if [ "$success" != "true" ]; then
        message=$(echo "$body" | jq -r '.message // empty')
        echo -e "${RED}❌ Endpoint rejected the file: $message${NC}" | tee -a "$CLIENT_LOG"
        return 1
    fi

    echo -e "${GREEN}✅ Upload succeeded for $file${NC}" | tee -a "$CLIENT_LOG"
}

# --- Upload latest JSON ---
upload_file "$LATEST_JSON"

echo -e "${GREEN}Upload completed.${NC}" | tee -a "$CLIENT_LOG"
