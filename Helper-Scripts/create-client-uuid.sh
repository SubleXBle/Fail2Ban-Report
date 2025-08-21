#!/bin/bash
# generate-client-uuid.sh
# Erstellt eine UUID für den Client und speichert sie unter /opt/Fail2Ban-Report/Settings/client-uuid.json

SETTINGS_DIR="/opt/Fail2Ban-Report/Settings"
UUID_FILE="$SETTINGS_DIR/client-uuid.json"

# Settings-Verzeichnis erstellen, falls es nicht existiert
mkdir -p "$SETTINGS_DIR"

# UUID generieren
UUID=$(cat /proc/sys/kernel/random/uuid)

# JSON schreiben
echo "{\"uuid\": \"$UUID\"}" > "$UUID_FILE"

# Berechtigungen setzen
chown root:www-data "$UUID_FILE"
chmod 0660 "$UUID_FILE"

echo "UUID generated and saved to $UUID_FILE:"
echo "$UUID"
