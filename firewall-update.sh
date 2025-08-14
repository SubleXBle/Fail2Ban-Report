#!/bin/bash

set -euo pipefail

# --- Configuration ---
BLOCKLIST_DIR="/path/to/web/archive"
LOGFILE="/var/log/Fail2Ban-Report.log"
LOGGING=true  # Set to true to enable logging

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

  JAIL_NAME=$(basename "$FILE" .blocklist.json)
  LOCKFILE="/tmp/${JAIL_NAME}.blocklist.lock"

  log "Processing blocklist: $FILE"

  # === Acquire lock ===
  exec {lock_fd}>"$LOCKFILE"
  if ! flock -x "$lock_fd"; then
    log "ERROR: Could not acquire lock for $JAIL_NAME"
    continue
  fi

  # Extract active and inactive IPs
  mapfile -t active_ips < <(jq -r '.[] | select(.active != false) | .ip' "$FILE")
  mapfile -t inactive_ips < <(jq -r '.[] | select(.active == false) | .ip' "$FILE")

  blocked_success=()

  # --- BLOCK: Collect all new IPs and block them ---
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

  # Reload UFW once after all block actions
  if ((${#blocked_success[@]} > 0)); then
    log "Reloading UFW after block actions"
    ufw reload
  fi

  # --- UNBLOCK: Process each inactive IP individually ---
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

  # --- JSON Update: pending=false for blocked_success, remove inactive entries ---
  tmp_file=$(mktemp)
  BLOCK_JSON=$(printf '%s\n' "${blocked_success[@]:-}" | jq -R . | jq -s .)
  jq --argjson ips "$BLOCK_JSON" '
    map(
      if (.ip as $ip | $ips | index($ip)) then .pending = false else . end
    )
    | map(select(.active != false))
  ' "$FILE" > "$tmp_file" && mv "$tmp_file" "$FILE"

  # Set ownership and permissions
  chown www-data:www-data "$FILE"
  chmod 644 "$FILE"

  # === Release lock ===
  flock -u "$lock_fd"
  exec {lock_fd}>&-
done

log "All blocklists processed successfully."

exit 0
