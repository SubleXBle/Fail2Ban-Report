#!/bin/bash
# Fail2Ban-Report-cronscript.sh

# Run Information gathering
./fail2ban_log2json.sh
# wait 5 seconds
sleep 5

# Run downlod-checker to see if updates available
./download-checker.sh
