#!/bin/bash

set -euo pipefail

BLOCKLIST_JSON="/path/to/archive/blocklist.json"
TABLE_NAME="filter"
FAMILY="inet"
CHAIN_NAME="fail2ban-blocklist"

# Check if jq is installed
if ! command -v jq &>/dev/null; then
  echo "ERROR: jq is not installed." >&2
  exit 1
fi

# Create table and chain if not exists
if ! sudo nft list table "$FAMILY" "$TABLE_NAME" &>/dev/null; then
  sudo nft add table "$FAMILY" "$TABLE_NAME"
fi

if ! sudo nft list chain "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" &>/dev/null; then
  sudo nft add chain "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" '{ type filter hook input priority 0; }'
fi

# Get currently blocked IPs in the chain
current_ips=$(sudo nft list chain "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" | grep -Po '(?<=ip saddr )[\d.]+')

# Get active IPs from JSON
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# Add active IPs not yet in the chain
for ip in $active_ips; do
  if ! echo "$current_ips" | grep -qw "$ip"; then
    sudo nft add rule "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" ip saddr "$ip" drop
    # echo "Blocked $ip" > /dev/null  # Uncomment for debug
  fi
done

# Get inactive IPs
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# Remove rules for inactive IPs
for ip in $inactive_ips; do
  # Get rule handles for the IP
  handles=$(sudo nft -a list chain "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" | grep "ip saddr $ip drop" | awk '{print $NF}')
  for handle in $handles; do
    sudo nft delete rule "$FAMILY" "$TABLE_NAME" "$CHAIN_NAME" handle "$handle"
    # echo "Unblocked $ip (handle $handle removed)" > /dev/null  # Uncomment for debug
  done
done

# Clean up JSON file: remove inactive entries
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"
# echo "Inactive entries removed from JSON." > /dev/null  # Uncomment for debug

exit 0
