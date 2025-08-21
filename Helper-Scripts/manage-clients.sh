#!/bin/bash
# manage-clients.sh
# CLI-Tool zur Verwaltung der Fail2Ban-Report Clients für den HTTPS-Endpoint (JSON + bcrypt)

# === Konfiguration ===
CLIENT_FILE="/opt/Fail2Ban-Report/Settings/client-list.json"

# JSON-Datei existiert nicht → erstellen
if [ ! -f "$CLIENT_FILE" ]; then
    echo "[]" > "$CLIENT_FILE"
fi

# === Hilfsfunktionen ===
function read_password() {
    read -sp "Password: " password
    echo
    read -sp "Confirm Password: " password_confirm
    echo
    if [ "$password" != "$password_confirm" ]; then
        echo "Passwords do not match. Aborting."
        exit 1
    fi
}

function add_client() {
    read -p "Username: " username
    read_password
    read -p "UUID: " uuid
    read -p "IP (optional, leer lassen für alle): " ip

    # Passwort in bcrypt hashen (PHP)
    hash=$(php -r "echo password_hash('$password', PASSWORD_BCRYPT);")

    # JSON-Eintrag anhängen
    tmp=$(mktemp)
    jq --arg u "$username" --arg p "$hash" --arg id "$uuid" --arg ip "$ip" \
       '. += [{"username":$u,"password":$p,"uuid":$id,"ip":$ip}]' "$CLIENT_FILE" > "$tmp" && mv "$tmp" "$CLIENT_FILE"

    echo "Client $username added."
}

function edit_client() {
    read -p "Username to edit: " username

    # Prüfen, ob Client existiert
    exists=$(jq --arg u "$username" 'map(select(.username==$u)) | length' "$CLIENT_FILE")
    if [ "$exists" -eq 0 ]; then
        echo "Client $username not found."
        exit 1
    fi

    read_password
    read -p "UUID: " uuid
    read -p "IP (optional, leer lassen für alle): " ip

    tmp=$(mktemp)
    jq --arg u "$username" --arg p "$(php -r "echo password_hash('$password', PASSWORD_BCRYPT);")" \
       --arg id "$uuid" --arg ip "$ip" \
       'map(if .username==$u then .password=$p | .uuid=$id | .ip=$ip else . end)' \
       "$CLIENT_FILE" > "$tmp" && mv "$tmp" "$CLIENT_FILE"

    echo "Client $username updated."
}

function delete_client() {
    read -p "Username to delete: " username
    tmp=$(mktemp)
    jq --arg u "$username" 'map(select(.username != $u))' "$CLIENT_FILE" > "$tmp" && mv "$tmp" "$CLIENT_FILE"
    echo "Client $username deleted (if existed)."
}

function list_clients() {
    echo "Current Clients:"
    jq -r '.[] | "Username: \(.username), UUID: \(.uuid), IP: \(.ip)"' "$CLIENT_FILE"
}

# === Hauptmenü ===
echo "Select action:"
echo "1) Add client"
echo "2) Edit client"
echo "3) Delete client"
echo "4) List clients"
read -p "Choice [1/2/3/4]: " action

case "$action" in
    1) add_client ;;
    2) edit_client ;;
    3) delete_client ;;
    4) list_clients ;;
    *) echo "Invalid choice"; exit 1 ;;
esac

# === Berechtigungen setzen ===
chown root:www-data "$CLIENT_FILE"
chmod 0660 "$CLIENT_FILE"
