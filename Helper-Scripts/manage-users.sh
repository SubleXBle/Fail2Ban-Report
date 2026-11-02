#!/bin/bash
# manage_users.sh
# CLI-Tool to manage Fail2Ban-Report Users (JSON + bcrypt)

USER_FILE="/opt/Fail2Ban-Report/Settings/users.json"

# Create JSON file if it does not exist
if [ ! -f "$USER_FILE" ]; then
    echo "[]" > "$USER_FILE"
fi

function add_user() {
    read -p "Username: " username
    
    # Validate username (0.5.2)
    if [[ ! "$username" =~ ^[A-Za-z0-9._-]+$ ]]; then
        echo "Error: Invalid username. Only alphanumeric characters, dots, underscores, and hyphens are allowed."
        exit 1
    fi

    read -sp "Password: " password
    echo
    
    # Validate against empty password (0.5.2)
    if [ -z "$password" ]; then
        echo "Error: Password cannot be empty."
        exit 1
    fi

    read -p "Role (admin/viewer): " role
    if [[ "$role" != "admin" && "$role" != "viewer" ]]; then
        echo "Error: Invalid role. Must be 'admin' or 'viewer'."
        exit 1
    fi

    # Secure password hashing via environment variable (Fix against Command Injection)
    hash=$(PW="$password" php -r 'echo password_hash(getenv("PW"), PASSWORD_BCRYPT);' 2>/dev/null)

    # Check if hashing was successful (e.g., if php-cli is missing)
    if [ -z "$hash" ]; then
        echo "Error: Password hashing failed. Is php-cli installed?"
        exit 1
    fi

    # Attach JSON entry
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
