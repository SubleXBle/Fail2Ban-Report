#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_JSON="/path/to/archive/blocklist.json"
LOGFILE="/var/log/fail2ban_blocklist.log"   # Log file path for cron output
LOGGING=false                                # Logging disabled by default; set to true to enable

# --- Set PATH to ensure commands are found ---
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

# --- Extract active and inactive IPs from JSON ---
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# --- Block new IPs ---
for ip in $active_ips; do
  if ! grep -qw "$ip" "$TMP_BLOCKED"; then
    log "Blocking IP: $ip"
    ufw deny from "$ip"
  fi
done

# --- Remove UFW rules for inactive IPs ---
for ip in $inactive_ips; do
  # Get UFW rules for this IP in reverse order to avoid shifting rule numbers on deletion
  mapfile -t rules < <(ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
  for rule in "${rules[@]}"; do
    rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
    log "Removing UFW rule #$rule_number for IP: $ip"
    ufw --force delete "$rule_number"
  done
done

# --- Clean up JSON by removing inactive entries ---
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"

# --- Set ownership and permissions for JSON file ---
chown www-data:www-data "$BLOCKLIST_JSON"
chmod 644 "$BLOCKLIST_JSON"

log "UFW blocklist updated successfully."

exit 0
