#!/bin/bash

BLOCKLIST_JSON="/pfad/zum/archive/blocklist.json"
CHAIN_NAME="fail2ban-blocklist"

# Prüfen ob iptables Chain existiert, sonst erstellen
iptables -L $CHAIN_NAME -n >/dev/null 2>&1
if [ $? -ne 0 ]; then
  iptables -N $CHAIN_NAME
  iptables -I INPUT -j $CHAIN_NAME
fi

# Aktuelle IPs aus der Chain lesen
current_ips=$(iptables -L $CHAIN_NAME -n --line-numbers | grep DROP | awk '{print $4}')

# IPs aus blocklist.json holen (nur aktive)
active_ips=$(jq -r '.[] | select(.active != false) | .ip' "$BLOCKLIST_JSON")

# IPs blockieren, die aktiv sind und noch nicht in iptables
for ip in $active_ips; do
  if ! echo "$current_ips" | grep -qw "$ip"; then
    iptables -I $CHAIN_NAME -s $ip -j DROP
    echo "Blocked $ip"
  fi
done

# IPs aus iptables entfernen, die in blocklist.json als inactive markiert sind oder nicht mehr in Liste
inactive_ips=$(jq -r '.[] | select(.active == false) | .ip' "$BLOCKLIST_JSON")

for ip in $inactive_ips; do
  # iptables Regelnummer herausfinden und löschen
  rule_num=$(iptables -L $CHAIN_NAME -n --line-numbers | grep "$ip" | awk '{print $1}')
  if [ ! -z "$rule_num" ]; then
    iptables -D $CHAIN_NAME $rule_num
    echo "Unblocked $ip"
  fi
done
