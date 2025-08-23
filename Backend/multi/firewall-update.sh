#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_DIR="/opt/Fail2Ban-Report/archive/blocklists"
LOGFILE="/var/log/Fail2Ban-Report.log"
LOGGING=true  # Set to true to enable logging

# Server credentials
CLIENT_USER="MyClientName"
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"
UPDATE_URL="https://my.server.tld/Fail2Ban-Report/endpoint/update.php"
DOWNLOAD_URL="https://my.server.tld/Fail2Ban-Report/endpoint/download.php"
BACKSYNC_URL="https://my.server.tld/Fail2Ban-Report/endpoint/backsync.php"

# --- Set PATH ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Logging function ---
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

# --- Check prerequisites ---
for cmd in jq ufw curl; do
  if ! command -v "$cmd" &>/dev/null; then
    log "ERROR: $cmd is not installed."
    exit 1
  fi
done

# --- Step 0: Check for updates ---
log "Checking for blocklist updates..."
UPDATE_RESPONSE=$(curl -s -X POST "$UPDATE_URL" \
  -F "username=$CLIENT_USER" \
  -F "password=$CLIENT_PASS" \
  -F "uuid=$CLIENT_UUID")

HAS_UPDATES=$(echo "$UPDATE_RESPONSE" | jq -r '.updates // false')

if [ "$HAS_UPDATES" = "true" ]; then
  log "Updates available. Waiting 10 seconds before download..."
  sleep 10

  # --- Step 1: Download blocklists ---
  log "Downloading updated blocklists..."
  curl -s -X POST "$DOWNLOAD_URL" \
    -F "username=$CLIENT_USER" \
    -F "password=$CLIENT_PASS" \
    -F "uuid=$CLIENT_UUID" \
    -o "$BLOCKLIST_DIR/$(date +%Y%m%d)_blocklists.tar.gz"

  if [ -s "$BLOCKLIST_DIR/$(date +%Y%m%d)_blocklists.tar.gz" ]; then
    log "Extracting downloaded blocklists..."
    tar -xzf "$BLOCKLIST_DIR/$(date +%Y%m%d)_blocklists.tar.gz" -C "$BLOCKLIST_DIR"
    rm "$BLOCKLIST_DIR/$(date +%Y%m%d)_blocklists.tar.gz"
  else
    log "WARNING: Downloaded blocklists file is empty."
  fi

  # Small pause before applying firewall rules
  sleep 5
else
  log "No blocklist updates available. Proceeding with local firewall update."
fi

# --- Step 2: Apply firewall rules (existing logic) ---
TMP_BLOCKED="/tmp/current_ufw_blocklist.txt"
ufw status numbered | grep "DENY IN" | awk '{print $3}' > "$TMP_BLOCKED" || true

for FILE in "$BLOCKLIST_DIR"/*.blocklist.json; do
  [ -e "$FILE" ] || continue

  JAIL_NAME=$(basename "$FILE" .blocklist.json)
  LOCKFILE="/tmp/${JAIL_NAME}.blocklist.lock"

  log "Processing blocklist: $FILE"

  # Acquire lock
  exec {lock_fd}>"$LOCKFILE"
  if ! flock -x "$lock_fd"; then
    log "ERROR: Could not acquire lock for $JAIL_NAME"
    continue
  fi

  # Extract active and inactive IPs
  mapfile -t active_ips < <(jq -r '.[] | select(.active != false) | .ip' "$FILE")
  mapfile -t inactive_ips < <(jq -r '.[] | select(.active == false) | .ip' "$FILE")

  blocked_success=()

  # BLOCK active IPs
  for ip in "${active_ips[@]}"; do
    if ! grep -qw "$ip" "$TMP_BLOCKED"; then
      log "Blocking IP: $ip"
      if ufw deny from "$ip"; then
        blocked_success+=("$ip")
      else
        log "Failed to block $ip via ufw"
      fi
    fi
  done

  # Reload UFW if needed
  if ((${#blocked_success[@]} > 0)); then
    log "Reloading UFW after block actions"
    ufw reload
  fi

  # UNBLOCK inactive IPs
  for ip in "${inactive_ips[@]}"; do
    mapfile -t rules < <(ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
    for rule in "${rules[@]}"; do
      rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
      if [[ -n "$rule_number" ]]; then
        log "Removing UFW rule #$rule_number for IP: $ip"
        ufw --force delete "$rule_number"
      fi
    done
  done

  # Update JSON: pending=false for blocked_success, remove inactive
  tmp_file=$(mktemp)
  BLOCK_JSON=$(printf '%s\n' "${blocked_success[@]:-}" | jq -R . | jq -s .)
  jq --argjson ips "$BLOCK_JSON" '
    map(
      if (.ip as $ip | $ips | index($ip)) then .pending = false else . end
    )
    | map(select(.active != false))
  ' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"

  chown www-data:www-data "$FILE"
  chmod 644 "$FILE"

  # Release lock
  flock -u "$lock_fd"
  exec {lock_fd}>&-
done

log "Firewall rules applied successfully."

# --- Step 3: Backsync updated blocklists ---
if [ "$HAS_UPDATES" = "true" ]; then
  log "Uploading updated blocklists to server (backsync)..."
  for FILE in "$BLOCKLIST_DIR"/*.blocklist.json; do
    [ -e "$FILE" ] || continue

    response=$(curl -s -w "\n%{http_code}" -X POST "$BACKSYNC_URL" \
      -F "username=$CLIENT_USER" \
      -F "password=$CLIENT_PASS" \
      -F "uuid=$CLIENT_UUID" \
      -F "file=@$FILE" || true)

    http_code=$(tail -n1 <<< "$response")
    body=$(sed '$d' <<< "$response")

    if [ "$http_code" -ne 200 ]; then
      log "ERROR: Upload failed for $FILE (HTTP $http_code)"
      continue
    fi

    success=$(echo "$body" | jq -r '.success // empty')
    if [ "$success" != "true" ]; then
      message=$(echo "$body" | jq -r '.message // empty')
      log "ERROR: Server rejected blocklist $FILE: $message"
      continue
    fi

    log "Backsync succeeded for $FILE"
  done
fi

log "Blocklist update workflow completed successfully."
exit 0
