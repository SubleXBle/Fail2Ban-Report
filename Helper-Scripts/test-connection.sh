#!/bin/bash
# test-client-send.sh
# Simuliert das Hochladen einer Events- oder Blocklist-JSON an den Endpoint
# mit detaillierter Ausgabe für Fehlerdiagnose

set -euo pipefail

# --- Colors ---
RED="\033[0;31m"
GREEN="\033[0;32m"
YELLOW="\033[0;33m"
NC="\033[0m" # No Color

# --- Configuration ---
ENDPOINT="https://your-server/Fail2Ban-Report/endpoint/index.php"
USERNAME="testclient"
PASSWORD="testpassword"
UUID="1234-5678-uuid"
TMP_DIR="/tmp/fb-test"
mkdir -p "$TMP_DIR"

# --- Create dummy fail2ban-events JSON ---
EVENTS_FILE="$TMP_DIR/fail2ban-events-$(date +%Y%m%d).json"
cat <<EOF > "$EVENTS_FILE"
[
  {
    "timestamp": "$(date -Iseconds)",
    "action": "Ban",
    "ip": "192.0.2.123",
    "jail": "sshd"
  },
  {
    "timestamp": "$(date -Iseconds)",
    "action": "Unban",
    "ip": "192.0.2.124",
    "jail": "sshd"
  }
]
EOF

# --- Create dummy blocklist JSON ---
BLOCKLIST_FILE="$TMP_DIR/sshd.blocklist.json"
cat <<EOF > "$BLOCKLIST_FILE"
[
  {
    "ip": "192.0.2.123",
    "jail": "sshd",
    "source": "manual",
    "timestamp": "$(date -Iseconds)",
    "active": true,
    "pending": true
  },
  {
    "ip": "192.0.2.125",
    "jail": "sshd",
    "source": "manual",
    "timestamp": "$(date -Iseconds)",
    "active": false,
    "pending": true
  }
]
EOF

# --- Function to POST JSON with detailed checks ---
upload_file() {
    local file=$1
    echo -e "${YELLOW}Uploading $file ...${NC}"

    response=$(curl -s -w "\n%{http_code}" -X POST "$ENDPOINT" \
        -F "username=$USERNAME" \
        -F "password=$PASSWORD" \
        -F "uuid=$UUID" \
        -F "file=@$file" || true)

    http_code=$(tail -n1 <<< "$response")
    body=$(sed '$d' <<< "$response")

    # --- Check HTTP / network level ---
    if [ "$http_code" -eq 0 ]; then
        echo -e "${RED}❌ Connection failed to $ENDPOINT${NC}"
        return 1
    fi

    echo -e "HTTP Status: $http_code"
    echo -e "Response Body: $body"

    if [ "$http_code" -ne 200 ]; then
        echo -e "${RED}❌ Upload failed with HTTP code $http_code${NC}"
        return 1
    fi

    # --- Check JSON success field ---
    success=$(echo "$body" | jq -r '.success // empty')
    if [ "$success" != "true" ]; then
        message=$(echo "$body" | jq -r '.message // empty')
        echo -e "${RED}❌ Endpoint rejected the file: $message${NC}"
        return 1
    fi

    echo -e "${GREEN}✅ Upload succeeded for $file${NC}"
}

# --- Upload both files ---
upload_file "$EVENTS_FILE"
upload_file "$BLOCKLIST_FILE"

echo -e "${GREEN}All test uploads completed.${NC}"
