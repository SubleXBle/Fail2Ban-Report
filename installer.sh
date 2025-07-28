#!/bin/bash

# Colors for output
NORMAL='\033[0;39m'
GREEN='\033[1;32m'
RED='\033[1;31m'
YELLOW='\033[33m'
BLUE='\033[34m'

# Repo info
REPO_URL="https://github.com/SubleXBle/Fail2Ban-Report.git"
BRANCH_NAME="latest"

# Default install path
TARGET_DIR="/opt/Fail2Ban-Report"

echo -e "${BLUE}--- Fail2Ban-Report Installer ---${NORMAL}"

# Ask for custom install path
read -rp "Do you want to use a custom installation path? (Y/N): " USE_CUSTOM_PATH
USE_CUSTOM_PATH=${USE_CUSTOM_PATH,,}  # to lowercase

if [[ "$USE_CUSTOM_PATH" == "y" ]]; then
    read -rp "Enter the full desired installation path (or leave empty for default: $TARGET_DIR): " USER_PATH
    if [[ -z "$USER_PATH" ]]; then
        echo "No path entered. Using default: $TARGET_DIR"
    else
        if mkdir -p "$USER_PATH" 2>/dev/null; then
            TARGET_DIR="$USER_PATH"
            echo "Using custom path: $TARGET_DIR"
        else
            echo -e "${YELLOW}Invalid path or no permission to create directory. Using default path: $TARGET_DIR${NORMAL}"
        fi
    fi
else
    echo "Using default installation path: $TARGET_DIR"
fi

echo -e "${BLUE}Checking if Git is installed...${NORMAL}"
if ! command -v git &> /dev/null; then
    echo -e "${RED}Error: Git is not installed. Please install Git and rerun this script.${NORMAL}"
    exit 1
fi

# Clone or update repo
if [ ! -d "$TARGET_DIR" ]; then
    echo -e "${YELLOW}Target directory does not exist. Cloning repository...${NORMAL}"
    git clone -b "$BRANCH_NAME" "$REPO_URL" "$TARGET_DIR"
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to clone repository.${NORMAL}"
        exit 1
    fi
else
    echo -e "${BLUE}Repository already exists. Pulling latest changes...${NORMAL}"
    cd "$TARGET_DIR" || { echo -e "${RED}Failed to cd into $TARGET_DIR${NORMAL}"; exit 1; }
    git pull origin "$BRANCH_NAME"
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to pull latest changes.${NORMAL}"
        exit 1
    fi
fi

# Set permissions for writable folders (archive & includes)
echo -e "${BLUE}Setting permissions for archive and includes folders...${NORMAL}"
mkdir -p "$TARGET_DIR/archive"
chmod 755 "$TARGET_DIR/archive"
chmod 755 "$TARGET_DIR/includes"

# Prompt for archive folder path in .sh script
FAIL2BAN_SH="$TARGET_DIR/fail2ban_log2json.sh"
echo -e "${BLUE}Configuring path to archive folder in fail2ban_log2json.sh...${NORMAL}"

# Escape slashes for sed usage
ESCAPED_ARCHIVE_PATH=$(echo "$TARGET_DIR/archive" | sed 's_/_\\/_g')

# Replace the archive path line (assumes a specific placeholder or variable line)
# Let's assume the line starts with ARCHIVE_PATH= or similar in the .sh
if grep -q "^ARCHIVE_PATH=" "$FAIL2BAN_SH"; then
    sed -i "s/^ARCHIVE_PATH=.*/ARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"/" "$FAIL2BAN_SH"
else
    # fallback: append at start if not found
    sed -i "1iARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"" "$FAIL2BAN_SH"
fi

echo -e "${GREEN}Archive path set to: $TARGET_DIR/archive${NORMAL}"

# Inform about .htaccess setup
echo -e "${BLUE}\nIMPORTANT:${NORMAL} Please configure your .htaccess file in the webroot to secure your app as described in the README."
echo "You may want to activate protections by uncommenting the relevant lines."
echo "Example .htaccess is included."

# Ask about setting up a daily cronjob for the JSON update script
read -rp "Do you want to install a daily cronjob to run fail2ban_log2json.sh at 3 AM? (Y/N): " INSTALL_CRON
INSTALL_CRON=${INSTALL_CRON,,}

if [[ "$INSTALL_CRON" == "y" ]]; then
    CRON_CMD="0 3 * * * $FAIL2BAN_SH > /dev/null 2>&1"
    (crontab -l 2>/dev/null | grep -v -F "$FAIL2BAN_SH"; echo "$CRON_CMD") | crontab -
    echo -e "${GREEN}Cronjob installed:${NORMAL} $CRON_CMD"
else
    echo -e "${YELLOW}Skipping cronjob setup. Remember to setup cron manually to generate JSON files daily.${NORMAL}"
fi

echo -e "${GREEN}\nInstallation complete!${NORMAL}"
echo "You can now configure your webserver to serve files from:"
echo "  $TARGET_DIR"
echo
echo "Remember to secure your web app with proper .htaccess or other access control."
echo "Also, check and customize fail2ban_log2json.sh if needed."
echo
echo "Happy selfhosting! 🚀"
