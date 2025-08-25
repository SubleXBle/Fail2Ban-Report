#!/bin/bash

# once a Client did his first Sync it creates a fail2ban folder inside his directory
# this Script creates the folders for blocklists if you want to automate it.

# Set the base path here
BASE_PATH="/path/to/your/archive"

# Check if the path exists
if [ ! -d "$BASE_PATH" ]; then
    echo "The specified path does not exist: $BASE_PATH"
    exit 1
fi

# Loop through each subdirectory in the base path
for DIR in "$BASE_PATH"/*/; do
    # Check if a "fail2ban" folder exists
    if [ -d "${DIR}fail2ban" ]; then
        echo "Processing folder: $DIR"
        
        # Create folders next to fail2ban, if they don't exist
        # ufw and stats are not needed by now - but will be needed soon
        # you can comment them out if you want
        mkdir -p "${DIR}blocklists"
        mkdir -p "${DIR}ufw"
        mkdir -p "${DIR}stats"
    fi
done

echo "Done!"
