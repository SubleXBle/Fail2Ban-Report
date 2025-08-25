#!/bin/bash

# --- Configuration ---
UPDATE_URL="https://SERVERURL/Fail2Ban-Report/endpoint/update.php"
DOWNLOAD_URL="https://SERVERURL/Fail2Ban-Report/endpoint/download.php"
USERNAME="SERVERNAME"
PASSWORD="PASSWORD"
UUID="UUID"
DEST_DIR="/path/to/downloaded/blocklists"

mkdir -p "$DEST_DIR"

# --- Update-Check ---
response=$(curl -s -X POST "$UPDATE_URL" \
  -F "username=$USERNAME" \
  -F "password=$PASSWORD" \
  -F "uuid=$UUID")

echo "Server Response:"
echo "$response"

updates=$(echo "$response" | jq -r '.updates | length')

if [ "$updates" -eq 0 ]; then
  echo "ℹ️ No updates available."
  exit 0
fi

echo "✅ Updates available: $updates blocklist(s)."

# --- download Blocklists ---
for FILE in $(echo "$response" | jq -r '.updates[]'); do
  echo "⬇️ Downloading $FILE ..."
  curl -s -X POST "$DOWNLOAD_URL?file=$FILE" \
    -d "username=$USERNAME" \
    -d "password=$PASSWORD" \
    -d "uuid=$UUID" \
    -o "$DEST_DIR/$FILE"

  if [ $? -eq 0 ] && [ -s "$DEST_DIR/$FILE" ]; then
    echo "✅ $FILE downloaded successfully."
  else
    echo "❌ Failed to download $FILE"
  fi
done

echo "🎉 All blocklists processed."

