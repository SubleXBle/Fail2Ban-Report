#!/bin/bash
# Fail2Ban-Report-cronscript.sh

# change dir for cronjobs
cd /opt/Fail2Ban-Report/Backend

LOGFILE="/opt/Fail2Ban-Report/cronjobs.log"

echo "----- cronrun start ------ $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOGFILE"

# Step 1: JSON generation
echo "📝 Step 1: Generating JSON from Fail2Ban logs..." >> "$LOGFILE"
./fail2ban_log2json.sh >> "$LOGFILE" 2>&1
sleep 1

# Step 2: Check for updates
echo "🔎 Step 2: Checking for updates..." >> "$LOGFILE"
./download-checker.sh >> "$LOGFILE" 2>&1
DOWNLOAD_STATUS=$?

# Step 3: Run firewall & sync only if updates available
if [ $DOWNLOAD_STATUS -eq 0 ]; then
    echo "✅ Updates found, running sync cycle..." >> "$LOGFILE"
    ./firewall-update.sh >> "$LOGFILE" 2>&1
    ./syncback.sh >> "$LOGFILE" 2>&1
else
    echo "ℹ️ No Updates, firewall & syncback skipped" >> "$LOGFILE"
fi

# Step 4: Always mark end of cronrun
echo "----- cronrun done! ------ $(date '+%Y-%m-%d %H:%M:%S')" >> "$LOGFILE"
