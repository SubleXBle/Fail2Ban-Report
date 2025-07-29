#!/bin/bash

set -euo pipefail

BLOCKLIST_JSON="/path/to/archive/blocklist.json"

# Check if jq is installed
if ! command -v jq &>/dev/null; then
  echo "ERROR: jq is not installed." >&2
  exit 1
fi

# Check if ufw is installed
if ! command -v ufw &>/dev/null; then
  echo "ERROR: ufw is not installed." >&2
  exit 1
fi

# Extract currently blocked IPs (only those we set)
ufw status numbered | grep "DENY IN" | awk '{print $3}' > /tmp/current_ufw_blocklist.txt || true

# Read active IPs from JSON
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# Block new IPs
for ip in $active_ips; do
  if ! grep -qw "$ip" /tmp/current_ufw_blocklist.txt; then
    echo "Blocking $ip"
    sudo ufw deny from "$ip"
  fi
done

# Read inactive IPs from JSON
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# Check and remove rules for each inactive IP
for ip in $inactive_ips; do
  # List and find all rules
  mapfile -t rules < <(sudo ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
  for rule in "${rules[@]}"; do
    rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
    echo "Removing rule $rule_number for $ip"
    ufw --force delete "$rule_number"
  done
done

# Clean up JSON (remove inactive entries)
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"

# After cleanup, ensure web interface retains access
chown www-data:www-data "$BLOCKLIST_JSON"
chmod 644 "$BLOCKLIST_JSON"

echo "UFW blocklist updated."
