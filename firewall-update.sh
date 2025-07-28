#!/bin/bash

set -euo pipefail

BLOCKLIST_JSON="/path/to/archive/blocklist.json"

# Prüfen ob jq installiert ist
if ! command -v jq &>/dev/null; then
  echo "ERROR: jq ist nicht installiert." >&2
  exit 1
fi

# Prüfen ob ufw installiert ist
if ! command -v ufw &>/dev/null; then
  echo "ERROR: ufw ist nicht installiert." >&2
  exit 1
fi

# Aktuell blockierte IPs extrahieren (nur die von uns gesetzten)
ufw status numbered | grep "DENY IN" | awk '{print $3}' > /tmp/current_ufw_blocklist.txt || true

# Aktive IPs aus JSON lesen
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# Neue IPs blockieren
for ip in $active_ips; do
  if ! grep -qw "$ip" /tmp/current_ufw_blocklist.txt; then
    echo "Blockiere $ip"
    sudo ufw deny from "$ip"
  fi
done

# Inaktive IPs freigeben
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# Für jede inaktive IP prüfen, ob blockiert – und ggf. entfernen
for ip in $inactive_ips; do
  # Alle Regeln auflisten und suchen
  mapfile -t rules < <(sudo ufw status numbered | grep "$ip" | grep "DENY IN" | tac)
  for rule in "${rules[@]}"; do
    rule_number=$(echo "$rule" | awk -F'[][]' '{print $2}')
    echo "Entferne Regel $rule_number für $ip"
    sudo ufw --force delete "$rule_number"
  done
done

# JSON bereinigen (inaktive Einträge löschen)
tmp_file=$(mktemp)
jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"

echo "UFW-Blocklist aktualisiert."
