#!/bin/bash
# manage-clients.sh
# CLI-Tool to manage Fail2Ban-Report Clients for the HTTPS-Endpoint (JSON + bcrypt)

# === Configuration ===
CLIENT_FILE="/opt/Fail2Ban-Report/Settings/client-list.json"

# JSON-File does not exist → create
if [ ! -f "$CLIENT_FILE" ]; then
    echo "[]" > "$CLIENT_FILE"
fi

# === Helperfunction ===
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
    read -p "IP (optional): " ip

    # Password in bcrypt hash (PHP)
    hash=$(php -r "echo password_hash('$password', PASSWORD_BCRYPT);")

    # attach JSON entry
    tmp=$(mktemp)
    jq --arg u "$username" --arg p "$hash" --arg id "$uuid" --arg ip "$ip" \
       '. += [{"username":$u,"password":$p,"uuid":$id,"ip":$ip}]' "$CLIENT_FILE" > "$tmp" && mv "$tmp" "$CLIENT_FILE"

    echo "Client $username added."
}

function edit_client() {
    read -p "Username to edit: " username

    # Check, if Client exists
    exists=$(jq --arg u "$username" 'map(select(.username==$u)) | length' "$CLIENT_FILE")
    if [ "$exists" -eq 0 ]; then
        echo "Client $username not found."
        exit 1
    fi

    read_password
    read -p "UUID: " uuid
    read -p "IP (optional): " ip

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

# === Main Menu ===
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

# === set ownership ===
chown root:www-data "$CLIENT_FILE"
chmod 0660 "$CLIENT_FILE"
