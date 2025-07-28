#!/bin/bash

set -euo pipefail

BLOCKLIST_JSON="/path/to/archive/blocklist.json"
CHAIN_NAME="fail2ban-blocklist"

# Check if jq is installed
if ! command -v jq &>/dev/null; then
  echo "ERROR: jq is not installed." >&2
  exit 1
fi

# Create chain if it does not exist
if ! iptables -L "$CHAIN_NAME" -n >/dev/null 2>&1; then
  iptables -N "$CHAIN_NAME"
fi

# Insert chain into INPUT if not already present
if ! iptables -C INPUT -j "$CHAIN_NAME" >/dev/null 2>&1; then
  iptables -I INPUT -j "$CHAIN_NAME"
fi

# Get currently blocked IPs in the chain
current_ips=$(iptables -L "$CHAIN_NAME" -n --line-numbers | grep DROP | awk '{print $4}')

# Get IPs marked as active=true from JSON
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# Block active IPs that are not yet in the chain
for ip in $active_ips; do
  if ! echo "$current_ips" | grep -qw "$ip"; then
    iptables -I "$CHAIN_NAME" -s "$ip" -j DROP
    # echo "Blocked $ip" > /dev/null   # Uncomment for debug output
  fi
done

# Get IPs marked as active=false from JSON (inactive IPs)
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# Remove inactive IPs from the chain
for ip in $inactive_ips; do
  # Get all rule numbers matching the IP, sorted descending for safe deletion
  rule_nums=$(iptables -L "$CHAIN_NAME" -n --line-numbers | grep "$ip" | awk '{print $1}' | sort -rn)
  for rule_num in $rule_nums; do
    iptables -D "$CHAIN_NAME" "$rule_num"
    # echo "Unblocked $ip (removed rule $rule_num)" > /dev/null   # Uncomment for debug output
  done
done

# Remove inactive entries from JSON and overwrite file
tmp_file=$(mktemp)

jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"
# echo "Inactive entries removed from JSON." > /dev/null  # Uncomment for debug output

exit 0
