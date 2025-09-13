#!/bin/bash
# This is the Logfile-Reader for the local installation
# You have to edit the OUTPUT_JSON_DIR to fit your Webserver Installation
#
LOGFILE="/var/log/fail2ban.log"
OUTPUT_JSON_DIR="/var/www/html/Fail2Ban-Report/archive/<SERVERNAME>/fail2ban"
# <SERVERNAME> is the Name of your local Server Folder in archive/

TODAY=$(date +"%Y-%m-%d")
OUTPUT_JSON_FILE="$OUTPUT_JSON_DIR/fail2ban-events-$(date +"%Y%m%d").json"
mkdir -p "$OUTPUT_JSON_DIR"

# IPv4 regex: nnn.nnn.nnn.nnn (0-255 simplified to 0-999 for awk)
IPv4='([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})'
# IPv6 regex: blocks of hex split by collon, allowed "::" short format
IPv6='(([0-9A-Fa-f]{1,4}:){1,7}[0-9A-Fa-f]{1,4}|([0-9A-Fa-f]{1,4}:){1,7}:|:([0-9A-Fa-f]{1,4}:){1,7}[0-9A-Fa-f]{1,4}|::)'
# complete IPv4 and IPv6 pattern
IP_PATTERN="($IPv4|$IPv6)"

echo "[" > "$OUTPUT_JSON_FILE"

# Grep all relevant Events
grep -E "(Ban|Unban)" "$LOGFILE" | awk -v today="$TODAY" -v ip_pattern="$IP_PATTERN" '
{
    timestamp = $1 " " $2;
    if (index(timestamp, today) != 1) next;

    action = "";
    ip = "";

    if ($0 ~ /Increase Ban/) {
        action = "Increase Ban";
        match($0, action " " ip_pattern, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Ban/) {
        action = "Ban";
        match($0, action " " ip_pattern, m);
        if (m[1]) ip = m[1];
    } else if ($0 ~ /Unban/) {
        action = "Unban";
        match($0, action " " ip_pattern, m);
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
