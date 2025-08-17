#!/bin/bash
# manage_users.sh
# CLI-Tool zur Verwaltung der Fail2Ban-Report User (JSON + bcrypt)

USER_FILE="/opt/Fail2Ban-Report/Settings/users.json"

# JSON-Datei existiert nicht → erstellen
if [ ! -f "$USER_FILE" ]; then
    echo "[]" > "$USER_FILE"
fi

function add_user() {
    read -p "Username: " username
    read -sp "Password: " password
    echo
    read -p "Role (admin/viewer): " role

    # Passwort in bcrypt hashen (PHP verwenden)
    hash=$(php -r "echo password_hash('$password', PASSWORD_BCRYPT);")

    # JSON-Eintrag anhängen
    tmp=$(mktemp)
    jq --arg u "$username" --arg p "$hash" --arg r "$role" '. += [{"username":$u,"password":$p,"role":$r}]' "$USER_FILE" > "$tmp" && mv "$tmp" "$USER_FILE"
    echo "User $username added."
}

function del_user() {
    read -p "Username to delete: " username
    tmp=$(mktemp)
    jq --arg u "$username" 'map(select(.username != $u))' "$USER_FILE" > "$tmp" && mv "$tmp" "$USER_FILE"
    echo "User $username deleted (if existed)."
}

echo "Select action:"
echo "1) Add user"
echo "2) Delete user"
read -p "Choice [1/2]: " action

case "$action" in
    1) add_user ;;
    2) del_user ;;
    *) echo "Invalid choice"; exit 1 ;;
esac

chown root:www-data "$USER_FILE"
chmod 0660 "$USER_FILE"
