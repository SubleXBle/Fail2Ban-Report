LOGFILE="/var/log/fail2ban.log"
OUTPUT_JSON_DIR="/var/www/Fail2Ban-Report/archive"

TODAY=$(date +"%Y-%m-%d")
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$(date +"%Y%m%d").json"
mkdir -p "$OUTPUT_JSON_DIR"

echo "[" > "$OUTPUT_JSON_FILE"

# Grep alle relevanten Events
grep -E "(Ban|Unban)" "$LOGFILE" | awk -v today="$TODAY" '
{
    timestamp = $1 " " $2;
    if (index(timestamp, today) != 1) next;

    action = "";
    ip = "";
    if ($0 ~ /Increase Ban/) {
        action = "Increase Ban";
        match($0, /Increase Ban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Ban/) {
        action = "Ban";
        match($0, /Ban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Unban/) {
        action = "Unban";
        match($0, /Unban ([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/, m);
        if (m[1]) ip = m[1];
    }

    # Extract jail from first non-numeric bracketed section
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

    if (ip != "") {
        printf "  {\n    \"timestamp\": \"%s\",\n    \"action\": \"%s\",\n    \"ip\": \"%s\",\n    \"jail\": \"%s\"\n  },\n", timestamp, action, ip, jail;
    }
}
' >> "$OUTPUT_JSON_FILE"

# Remove last comma
if [ -s "$OUTPUT_JSON_FILE" ]; then
    sed -i '$ s/},/}/' "$OUTPUT_JSON_FILE"
fi

echo "]" >> "$OUTPUT_JSON_FILE"
echo "✅ JSON created: $OUTPUT_JSON_FILE"
