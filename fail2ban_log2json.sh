#!/bin/bash

# === Configuration ===
LOGFILE="/var/log/fail2ban.log"  # This is the Fail2Ban log file - change if your Fail2Ban log is elsewhere
OUTPUT_JSON_DIR="/var/www/Fail2Ban/archive"  # Folder on your webserver - adjust as needed

# === Preparation ===
TODAY=$(date +"%Y%m%d")
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$TODAY.json"

mkdir -p "$OUTPUT_JSON_DIR"

# === Processing ===
echo "[" > "$OUTPUT_JSON_FILE"

grep -E "Ban |Unban " "$LOGFILE" | awk '
{
    timestamp = $1 " " $2;

    # Get action (Ban or Unban)
    action = $(NF-1);
    ip = $NF;

    # Extract all square brackets content
    text = $0;
    c = 0;
    delete arr;
    while (match(text, /\[[^]]+\]/)) {
        content = substr(text, RSTART+1, RLENGTH-2);
        c++;
        arr[c] = content;
        text = substr(text, RSTART + RLENGTH);
    }

    jail = "unknown";
    for(i=1; i<=c; i++) {
        # First entry that is not a numeric code
        if (arr[i] !~ /^[0-9]+$/) {
            jail = arr[i];
            break;
        }
    }

    printf "  {\n    \"timestamp\": \"%s\",\n    \"action\": \"%s\",\n    \"ip\": \"%s\",\n    \"jail\": \"%s\"\n  },\n", timestamp, action, ip, jail;
}
' >> "$OUTPUT_JSON_FILE"

# Remove last comma (if any entries)
if [ -s "$OUTPUT_JSON_FILE" ]; then
    sed -i '$ s/},/}/' "$OUTPUT_JSON_FILE"
fi

echo "]" >> "$OUTPUT_JSON_FILE"

# === Result display ===
echo "✅ JSON created: $OUTPUT_JSON_FILE"
