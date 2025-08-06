#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_DIR="/var/www/Fail2Ban-Report/archive"
LOGFILE="/opt/Fail2Ban-Report/fail2ban_blocklist.log"
LOGGING=false  # Set to true to enable logging

# --- Set PATH ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Logging function ---
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

# --- Check prerequisites ---
if ! command -v jq &>/dev/null; then
  log "ERROR: jq is not installed."
  exit 1
fi

if ! command -v ufw &>/dev/null; then
  log "ERROR: ufw is not installed."
  exit 1
fi

# --- Get currently blocked IPs from UFW ---
TMP_BLOCKED="/tmp/current_ufw_blocklist.txt"
ufw status numbered | grep "DENY IN" | awk '{print $3}' > "$TMP_BLOCKED" || true

# --- Loop through all blocklist files ---
for FILE in "$BLOCKLIST_DIR"/*.blocklist.json; do
  [ -e "$FILE" ] || continue  # skip if no files match

  log "Processing blocklist: $FILE"

  # Extract active and inactive IPs
  active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$FILE")
  inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$FILE")

  # Block new IPs
  for ip in $active_ips; do
    if ! grep -qw "$ip" "$TMP_BLOCKED"; then
      log "Blocking IP: $ip"
      ufw deny from "$ip"
    fi
  done

  # Remove UFW rules for inactive IPs
  for ip in $inactive_ips; do
    # Reverse order to avoid shifting rule numbers
    mapfile -t rules < <(ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
    for rule in "${rules[@]}"; do
      rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
      log "Removing UFW rule #$rule_number for IP: $ip"
      ufw --force delete "$rule_number"
    done
  done

  # Clean up JSON by removing inactive entries
  tmp_file=$(mktemp)
  jq 'map(select(.active != false))' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"

  # Set ownership and permissions
  chown www-data:www-data "$FILE"
  chmod 644 "$FILE"
done

log "All blocklists processed successfully."

exit 0
