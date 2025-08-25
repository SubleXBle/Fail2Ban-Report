#!/bin/bash
set -euo pipefail

# === Config laden ===
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/config.env"

mkdir -p "$BLOCKLIST_DIR"

# --- Update-Check ---
response=$(curl -s -X POST "$UPDATE_URL" \
  -F "username=$CLIENT_USER" \
  -F "password=$CLIENT_PASS" \
  -F "uuid=$CLIENT_UUID")

echo "Server Response:"
echo "$response"

updates=$(echo "$response" | jq -r '.updates | length')

if [ "$updates" -eq 0 ]; then
  echo "ℹ️ No updates available."
  exit 1
fi

echo "✅ Updates available: $updates blocklist(s)."

# --- download Blocklists ---
for FILE in $(echo "$response" | jq -r '.updates[]'); do
  echo "⬇️ Downloading $FILE ..."
  curl -s -X POST "$DOWNLOAD_URL?file=$FILE" \
    -d "username=$CLIENT_USER" \
    -d "password=$CLIENT_PASS" \
    -d "uuid=$CLIENT_UUID" \
    -o "$BLOCKLIST_DIR/$FILE"

  if [ $? -eq 0 ] && [ -s "$BLOCKLIST_DIR/$FILE" ]; then
    echo "✅ $FILE downloaded successfully."
  else
    echo "❌ Failed to download $FILE"
  fi
done

echo "🎉 All blocklists processed."
