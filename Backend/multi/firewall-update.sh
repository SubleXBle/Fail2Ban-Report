#!/bin/bash

set -euo pipefail

# === Configuration ===
BLOCKLIST_DIR="/opt/Fail2Ban-Report/archive/blocklists"
LOGFILE="/opt/Fail2Ban-Report/Firewall.log"
LOGGING=true

# Client Credentials for backsync
CLIENT_USER="MyClientName"
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"
BACKSYNC_URL="https://my.server.tld/Fail2Ban-Report/endpoint/backsync.php"
CLIENT_LOG="/var/log/fail2ban-report-client.log"

# --- PATH ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Logging ---
log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" | tee -a "$LOGFILE"
  fi
}

# --- Prerequisites ---
for cmd in jq ufw; do
  if ! command -v "$cmd" &>/dev/null; then
    log "ERROR: $cmd is not installed."
    exit 1
  fi
done

# --- Get currently blocked IPs from UFW ---
TMP_BLOCKED="/tmp/current_ufw_blocklist.txt"
ufw status numbered | grep "DENY IN" | awk '{print $3}' > "$TMP_BLOCKED" || true

# --- Loop through all blocklist files ---
PROCESSED_FILES=()

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

  # --- BLOCK new IPs ---
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

  # Reload UFW once after blocking
  if ((${#blocked_success[@]} > 0)); then
    log "Reloading UFW after block actions"
    ufw reload
  fi

  # --- UNBLOCK inactive IPs ---
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

  # --- JSON Update: pending=false for newly blocked, remove inactive ---
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

  # Release lock
  flock -u "$lock_fd"
  exec {lock_fd}>&-

  PROCESSED_FILES+=("$FILE")
done

log "All blocklists processed. Preparing to backsync ${#PROCESSED_FILES[@]} file(s)..."

# --- Upload processed blocklists to backsync.php ---
if [ ${#PROCESSED_FILES[@]} -gt 0 ]; then
  CURL_CMD=(curl -s -w "\n%{http_code}" -X POST "$BACKSYNC_URL" \
      -F "username=$CLIENT_USER" \
      -F "password=$CLIENT_PASS" \
      -F "uuid=$CLIENT_UUID")

  for FILE in "${PROCESSED_FILES[@]}"; do
      CURL_CMD+=(-F "file[]=@$FILE")
  done

  RESPONSE="$("${CURL_CMD[@]}")"
  HTTP_CODE=$(tail -n1 <<< "$RESPONSE")
  BODY=$(sed '$d' <<< "$RESPONSE")

  echo "$(date '+%Y-%m-%d %H:%M:%S') HTTP Status: $HTTP_CODE" | tee -a "$CLIENT_LOG"
  echo "$(date '+%Y-%m-%d %H:%M:%S') Response Body: $BODY" | tee -a "$CLIENT_LOG"

  if [ "$HTTP_CODE" -eq 200 ] && [ "$(echo "$BODY" | jq -r '.success // empty')" = "true" ]; then
      log "✅ Blocklists successfully uploaded to backsync.php"
  else
      log "❌ Failed to upload blocklists"
  fi
fi

log "Firewall blocklists processed and synchronized successfully."
exit 0
