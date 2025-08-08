#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_DIR="/var/www/vhosts/suble.org/xbkupx/Fail2Ban-Report/archive"
LOGFILE="/opt/Fail2Ban-Report/firewalld_blocklist.log"
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

if ! command -v firewall-cmd &>/dev/null; then
  log "ERROR: firewalld is not installed."
  exit 1
fi

# --- Get currently blocked IPs in firewalld ---
current_ips=$(firewall-cmd --list-rich-rules | grep -oP 'source address="\K[0-9\.]+' || true)

# --- Loop through all blocklist files ---
for FILE in "$BLOCKLIST_DIR"/*.blocklist.json; do
  [ -e "$FILE" ] || continue  # skip if no files match

  log "Processing blocklist: $FILE"

  # Extract active and inactive IPs
  active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$FILE")
  inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$FILE")

  # Block new IPs and update pending flag
  for ip in $active_ips; do
    if ! echo "$current_ips" | grep -qw "$ip"; then
      log "Blocking IP: $ip"
      if firewall-cmd --permanent --add-rich-rule="rule family='ipv4' source address='$ip' reject"; then
        log "Blocked $ip successfully, updating pending flag"
        # Update pending to false for this IP in JSON
        tmp_file=$(mktemp)
        jq --arg ip "$ip" 'map(if .ip == $ip then .pending = false else . end)' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"
      else
        log "Failed to block $ip via firewalld"
      fi
    fi
  done

  # Remove firewalld rules for inactive IPs
  for ip in $inactive_ips; do
    if echo "$current_ips" | grep -qw "$ip"; then
      log "Removing firewalld rule for IP: $ip"
      if firewall-cmd --permanent --remove-rich-rule="rule family='ipv4' source address='$ip' reject"; then
        log "Removed rule for $ip successfully"
      else
        log "Failed to remove rule for $ip"
      fi
    fi
  done

  # Reload firewalld to apply changes
  firewall-cmd --reload
  log "Reloaded firewalld rules"

  # Clean up JSON by removing inactive entries
  tmp_file=$(mktemp)
  jq 'map(select(.active != false))' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"

  # Set ownership and permissions
  chown www-data:www-data "$FILE"
  chmod 644 "$FILE"
done

log "All blocklists processed successfully."

exit 0
