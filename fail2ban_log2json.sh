#!/bin/bash

# === Configuration ===
LOGFILE="/var/log/fail2ban.log"  # This is the Fail2Ban log file - change if your Fail2Ban log is elsewhere
OUTPUT_JSON_DIR="/var/www/Fail2Ban/archive"  # Folder on your webserver - adjust as needed

# === Preparation ===
TODAY=$(date +"%Y-%m-%d")                     # Current date in the format "YYYY-MM-DD"
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$(date +"%Y%m%d").json"

mkdir -p "$OUTPUT_JSON_DIR"

# === Processing ===
echo "[" > "$OUTPUT_JSON_FILE"

grep -E "Ban |Unban " "$LOGFILE" | awk -v today="$TODAY" '
{
    timestamp = $1 " " $2;

    # Only process entries from today
    if (index(timestamp, today) != 1) {
        next;
    }

    action = $(NF-1);
    ip = $NF;

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
        if (arr[i] !~ /^[0-9]+$/) {
            jail = arr[i];
            break;
        }
    }

    printf "  {\n    \"timestamp\": \"%s\",\n    \"action\": \"%s\",\n    \"ip\": \"%s\",\n    \"jail\": \"%s\"\n  },\n", timestamp, action, ip, jail;
}
' >> "$OUTPUT_JSON_FILE"

# Remove the trailing comma, if present
if [ -s "$OUTPUT_JSON_FILE" ]; then
    sed -i '$ s/},/}/' "$OUTPUT_JSON_FILE"
fi

echo "]" >> "$OUTPUT_JSON_FILE"

# === Final message ===
echo "✅ JSON created: $OUTPUT_JSON_FILE"
