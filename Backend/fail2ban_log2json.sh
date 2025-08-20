#!/bin/bash

# === Configuration ===
LOGFILE="/var/log/fail2ban.log"              # Path to Fail2Ban log file (adjust if needed)
OUTPUT_JSON_DIR="/opt/Fail2Ban-Report/archive/YOUR-HOSTNAME/fail2ban"  # Target folder for JSON files (adjust if needed)

# === Preparation ===
TODAY=$(date +"%Y-%m-%d")                     # current date in format "YYYY-MM-DD"
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$(date +"%Y%m%d").json"

mkdir -p "$OUTPUT_JSON_DIR"

# === Processing ===
echo "[" > "$OUTPUT_JSON_FILE"

grep -E "(^|[^A-Za-z])(Ban|Unban) " "$LOGFILE" | awk -v today="$TODAY" '
{
    timestamp = $1 " " $2;

    # Process only entries from today
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

# Remove last comma if present
if [ -s "$OUTPUT_JSON_FILE" ]; then
    sed -i '$ s/},/}/' "$OUTPUT_JSON_FILE"
fi

echo "]" >> "$OUTPUT_JSON_FILE"

# === Completion message ===
echo "✅ JSON created: $OUTPUT_JSON_FILE"
