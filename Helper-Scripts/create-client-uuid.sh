#!/bin/bash
# generate-client-uuid.sh
# This will create a UUID for this Client that will get saved under the path: /opt/Fail2Ban-Report/Settings/client-uuid.json

SETTINGS_DIR="/opt/Fail2Ban-Report/Settings"
UUID_FILE="$SETTINGS_DIR/client-uuid.json"

# create Settings directory if not exist
mkdir -p "$SETTINGS_DIR"

# generate UUID
UUID=$(cat /proc/sys/kernel/random/uuid)

# write JSON
echo "{\"uuid\": \"$UUID\"}" > "$UUID_FILE"

# Set ownership
chown root:www-data "$UUID_FILE"
chmod 0660 "$UUID_FILE"

echo "UUID generated and saved to $UUID_FILE:"
echo "$UUID"
