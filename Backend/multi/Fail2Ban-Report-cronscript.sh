#!/bin/bash
# Fail2Ban-Report-cronscript.sh

LOGFILE="/opt/Fail2Ban-Report/cronjobs.log"

echo "----- cronrun start ------" >> $LOGFILE

# Step 1: JSON generation
./fail2ban_log2json.sh >> $LOGFILE 2>&1
sleep 5

# Step 2: Check for updates
./download-checker.sh >> $LOGFILE 2>&1
DOWNLOAD_STATUS=$?

# Step 3: If Updates available run sync cycle
if [ $DOWNLOAD_STATUS -eq 0 ]; then
    echo "✅ Updates found, running sync cycle" >> $LOGFILE
    ./firewall-update.sh >> $LOGFILE 2>&1 && ./syncback.sh >> $LOGFILE 2>&1
else
    echo "ℹ️ No Updates, firewall & syncback skipped" >> $LOGFILE
fi

echo "----- cronrun done! ------" >> $LOGFILE
