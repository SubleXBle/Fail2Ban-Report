#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_DIR="/var/www/vhosts/suble.org/xbkupx/Fail2Ban-Report/archive"
LOGFILE="/opt/Fail2Ban-Report/fail2ban_blocklist.log"
LOGGING=false  # Set to true to enable logging
NFT_CHAIN="fail2ban-blocklist"
NFT_TABLE="filter"
NFT_CHAIN_HOOK="input"

# --- Set PATH ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Logging function ---
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

# --- Check prerequisites ---
if ! command -v nft &>/dev/null; then
  log "ERROR: nft command not found."
  exit 1
fi

if ! command -v jq &>/dev/null; then
  log "ERROR: jq is not installed."
  exit 1
fi

# --- Ensure the fail2ban-blocklist chain exists and is hooked into input chain ---
if ! nft list chain $NFT_TABLE $NFT_CHAIN &>/dev/null; then
  log "Creating nftables chain $NFT_CHAIN in table $NFT_TABLE"
  nft add chain $NFT_TABLE $NFT_CHAIN '{ type filter hook input priority 0 ; }'
fi

# Check if $NFT_CHAIN is referenced in input chain, else add jump rule
if ! nft list chain $NFT_TABLE $NFT_CHAIN_HOOK | grep -q "jump $NFT_CHAIN"; then
  log "Adding jump to $NFT_CHAIN in $NFT_CHAIN_HOOK chain"
  nft insert rule $NFT_TABLE $NFT_CHAIN_HOOK jump $NFT_CHAIN
fi

# --- Get currently blocked IPs in the fail2ban-blocklist chain ---
current_blocked_ips=($(nft list chain $NFT_TABLE $NFT_CHAIN 2>/dev/null | grep -oP '(?<=ip saddr )[0-9\.]+' | sort -u))

# --- Loop through all blocklist files ---
for FILE in "$BLOCKLIST_DIR"/*.blocklist.json; do
  [ -e "$FILE" ] || continue  # skip if no files match

  log "Processing blocklist: $FILE"

  # Extract active and inactive IPs
  active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$FILE")
  inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$FILE")

  # Block new IPs and update pending flag
  for ip in $active_ips; do
    if [[ ! " ${current_blocked_ips[*]} " =~ " $ip " ]]; then
      log "Blocking IP: $ip"
      if nft add rule $NFT_TABLE $NFT_CHAIN ip saddr "$ip" drop; then
        log "Blocked $ip successfully, updating pending flag"
        # Update pending to false for this IP in JSON
        tmp_file=$(mktemp)
        jq --arg ip "$ip" 'map(if .ip == $ip then .pending = false else . end)' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"
      else
        log "Failed to block $ip via nftables"
      fi
    fi
  done

  # Remove nftables rules for inactive IPs
  for ip in $inactive_ips; do
    # Find all rule handles matching this IP in the fail2ban-blocklist chain
    handles=$(nft list chain $NFT_TABLE $NFT_CHAIN | grep -B 4 "ip saddr $ip" | grep handle | grep -oP 'handle \K[0-9]+')
    for handle in $handles; do
      log "Removing nftables rule handle $handle for IP: $ip"
      nft delete rule $NFT_TABLE $NFT_CHAIN handle "$handle"
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
