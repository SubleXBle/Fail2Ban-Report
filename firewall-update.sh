#!/bin/bash

set -euo pipefail

# --- Konfiguration ---
BLOCKLIST_JSON="/path/to/archive/blocklist.json"
LOGFILE="/var/log/fail2ban_blocklist.log"   # Logfile für Cron-Ausgaben
LOGGING=false                                # Standard Logging aus, kann auf true gesetzt werden

# --- Pfad setzen, damit alle Befehle gefunden werden ---
export PATH="/usr/sbin:/usr/bin:/sbin:/bin"

# --- Funktionen ---

log() {
  if [ "$LOGGING" = true ]; then
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $*" >> "$LOGFILE"
  fi
}

# --- Voraussetzungen prüfen ---
if ! command -v jq &>/dev/null; then
  log "ERROR: jq is not installed."
  exit 1
fi

if ! command -v ufw &>/dev/null; then
  log "ERROR: ufw is not installed."
  exit 1
fi

# --- Aktuelle blockierte IPs aus UFW auslesen ---
TMP_BLOCKED="/tmp/current_ufw_blocklist.txt"
ufw status numbered | grep "DENY IN" | awk '{print $3}' > "$TMP_BLOCKED" || true

# --- IPs aus JSON auslesen ---
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# --- Neue IPs blockieren ---
for ip in $active_ips; do
  if ! grep -qw "$ip" "$TMP_BLOCKED"; then
    log "Blocking $ip"
    ufw deny from "$ip"
  fi
done

# --- Inaktive IPs aus UFW entfernen ---
for ip in $inactive_ips; do
  # UFW-Statusnummern in umgekehrter Reihenfolge, damit das Löschen klappt
  mapfile -t rules < <(ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
  for rule in "${rules[@]}"; do
    rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
    log "Removing rule $rule_number for $ip"
    ufw --force delete "$rule_number"
  done
done

# --- JSON bereinigen ---
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"

# --- Berechtigungen setzen ---
chown www-data:www-data "$BLOCKLIST_JSON"
chmod 644 "$BLOCKLIST_JSON"

log "UFW blocklist updated."

exit 0
