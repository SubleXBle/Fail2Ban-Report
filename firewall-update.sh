#!/bin/bash

set -euo pipefail

BLOCKLIST_JSON="/pfad/zum/archive/blocklist.json"
CHAIN_NAME="fail2ban-blocklist"

# Prüfe, ob jq verfügbar ist
if ! command -v jq &>/dev/null; then
  echo "ERROR: jq ist nicht installiert." > /dev/null
  exit 1
fi

# Chain anlegen, falls nicht vorhanden
if ! iptables -L "$CHAIN_NAME" -n >/dev/null 2>&1; then
  iptables -N "$CHAIN_NAME"
fi

# Chain in INPUT-Chain einfügen, falls nicht bereits drin
if ! iptables -C INPUT -j "$CHAIN_NAME" >/dev/null 2>&1; then
  iptables -I INPUT -j "$CHAIN_NAME"
fi

# Hole aktuelle IP-Regeln aus der Chain (DROP-Regeln)
current_ips=$(iptables -L "$CHAIN_NAME" -n --line-numbers | grep DROP | awk '{print $4}')

# Lese Blocklist JSON: IPs mit active true
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# Blockiere aktive IPs, die noch nicht in der Chain sind
for ip in $active_ips; do
  if ! echo "$current_ips" | grep -qw "$ip"; then
    iptables -I "$CHAIN_NAME" -s "$ip" -j DROP
    echo "Blocked $ip" > /dev/null
  fi
done

# Lese IPs mit active false (inaktive)
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

# Entferne inaktive IPs aus der Chain
for ip in $inactive_ips; do
  rule_nums=$(iptables -L "$CHAIN_NAME" -n --line-numbers | grep "$ip" | awk '{print $1}' | sort -rn)
  for rule_num in $rule_nums; do
    iptables -D "$CHAIN_NAME" "$rule_num"
    echo "Unblocked $ip (Regel $rule_num entfernt)" > /dev/null
  done
done

# Entferne inaktive Einträge aus der JSON-Datei und schreibe zurück
tmp_file=$(mktemp)

jq 'map(select(.active != false))' "$BLOCKLIST_JSON" > "$tmp_file" && mv "$tmp_file" "$BLOCKLIST_JSON"
echo "Inactive entries removed from JSON."

exit 0
