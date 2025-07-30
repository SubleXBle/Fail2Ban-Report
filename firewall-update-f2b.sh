#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_JSON="/path/to/archive/blocklist.json"
LOGFILE="/var/log/fail2ban_blocklist.log"
LOGGING=false
JAIL="manualban"  # The jail to manage manual bans

# --- Set PATH ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Logging function ---
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

# --- Check prerequisites ---
for cmd in jq fail2ban-client; do
  if ! command -v "$cmd" &>/dev/null; then
    log "ERROR: $cmd is not installed."
    exit 1
  fi
done

# --- Get currently banned IPs from Fail2Ban ---
mapfile -t banned_ips < <(fail2ban-client status "$JAIL" | awk '/Banned IP list:/ {for(i=5;i<=NF;i++) print $i}')

# --- Extract active/inactive IPs from JSON ---
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# --- Ban active IPs not already banned ---
for ip in $active_ips; do
  if ! printf '%s\n' "${banned_ips[@]}" | grep -qw "$ip"; then
    log "Banning IP via Fail2Ban: $ip"
    fail2ban-client set "$JAIL" banip "$ip"
  fi
done

# --- Unban inactive IPs if still banned ---
for ip in $inactive_ips; do
  if printf '%s\n' "${banned_ips[@]}" | grep -qw "$ip"; then
    log "Unbanning IP via Fail2Ban: $ip"
    fail2ban-client set "$JAIL" unbanip "$ip"
  fi
done

# --- Clean up JSON (remove inactive entries) ---
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"

# --- Set ownership/permissions ---
chown www-data:www-data "$BLOCKLIST_JSON"
chmod 644 "$BLOCKLIST_JSON"

log "Fail2Ban blocklist updated successfully."

exit 0
