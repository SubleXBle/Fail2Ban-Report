#!/bin/bash

# ------------------------------
# Konfiguration
# ------------------------------
SERVER_URL="https://deinserver.tld/Fail2Ban-Report/endpoint/update.php"
USERNAME="alice"             # Dein Username auf dem Server
PASSWORD="dein_passwort"     # Passwort für Authentifizierung
UUID="123e4567-e89b-12d3-a456-426614174000" # UUID des Clients
TARGET_DIR="/var/lib/blocklists"  # Zielverzeichnis für die Blocklists

# ------------------------------
# JSON-Request an den Server
# ------------------------------
echo "Prüfe auf Updates für $USERNAME ..."

RESPONSE=$(curl -s -X POST "$SERVER_URL" \
    -H "Content-Type: application/json" \
    -d "{\"username\":\"$USERNAME\", \"password\":\"$PASSWORD\", \"uuid\":\"$UUID\"}")

# ------------------------------
# Prüfen ob der Request erfolgreich war
# ------------------------------
SUCCESS=$(echo "$RESPONSE" | jq -r '.success')

if [ "$SUCCESS" != "true" ]; then
    ERROR=$(echo "$RESPONSE" | jq -r '.error')
    echo "Fehler beim Abrufen: $ERROR"
    exit 1
fi

# ------------------------------
# Blocklists extrahieren und speichern
# ------------------------------
UPDATES=$(echo "$RESPONSE" | jq -r '.updates | keys[]?')

if [ -z "$UPDATES" ]; then
    echo "Keine Updates verfügbar."
    exit 0
fi

mkdir -p "$TARGET_DIR"

for BLOCKLIST in $UPDATES; do
    echo "Lade Blocklist $BLOCKLIST ..."
    CONTENT=$(echo "$RESPONSE" | jq -c ".updates[\"$BLOCKLIST\"]")
    echo "$CONTENT" > "$TARGET_DIR/$BLOCKLIST"
done

echo "Updates erfolgreich heruntergeladen."
