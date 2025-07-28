#!/bin/bash

# Colors for output
NORMAL='\033[0;39m'
GREEN='\033[1;32m'
RED='\033[1;31m'
YELLOW='\033[33m'
BLUE='\033[34m'

# Repository URL and Branch-Name
REPO_URL="https://github.com/SubleXBle/Fail2Ban-Report.git"
BRANCH_NAME="latest"

# Default paths
DEFAULT_WEBROOT="/var/www/html"
DEFAULT_SH_PATH="/opt/Fail2Ban-Report"

# Ask for Webroot path
echo -e "${YELLOW}Please enter your webroot path where the Fail2Ban-Report web tool should be installed."
echo "Press Enter to use default: $DEFAULT_WEBROOT${NORMAL}"
read -r TARGET_DIR
if [[ -z "$TARGET_DIR" ]]; then
  TARGET_DIR="$DEFAULT_WEBROOT"
fi

# Append repo folder name (Fail2Ban-Report)
TARGET_DIR="${TARGET_DIR%/}/Fail2Ban-Report"

# Ask for .sh script path
echo -e "${YELLOW}Please enter the path where the fail2ban_log2json.sh script should be placed."
echo "Press Enter to use default: $DEFAULT_SH_PATH${NORMAL}"
read -r SH_SCRIPT_PATH
if [[ -z "$SH_SCRIPT_PATH" ]]; then
  SH_SCRIPT_PATH="$DEFAULT_SH_PATH"
fi
SH_SCRIPT_PATH="${SH_SCRIPT_PATH%/}"

# Show summary
echo -e "${BLUE}Summary of installation paths:${NORMAL}"
echo "Webroot (repo): $TARGET_DIR"
echo "Shell script path: $SH_SCRIPT_PATH"

# Check if git is installed
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
  echo -e "${BLUE}Repository exists. Pulling latest changes...${NORMAL}"
  cd "$TARGET_DIR" || exit 1
  git pull origin "$BRANCH_NAME"
  if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to update repository.${NORMAL}"
    exit 1
  fi
fi

# Copy .sh script to chosen path
mkdir -p "$SH_SCRIPT_PATH"
cp "$TARGET_DIR/fail2ban_log2json.sh" "$SH_SCRIPT_PATH/"
chmod +x "$SH_SCRIPT_PATH/fail2ban_log2json.sh"
echo -e "${GREEN}Shell script copied and made executable at: $SH_SCRIPT_PATH/fail2ban_log2json.sh${NORMAL}"

# Configure OUTPUT_JSON_DIR in .sh script
ARCHIVE_DIR="$TARGET_DIR/archive"
SH_FILE="$SH_SCRIPT_PATH/fail2ban_log2json.sh"

if grep -q '^OUTPUT_JSON_DIR=' "$SH_FILE"; then
  sed -i "s|^OUTPUT_JSON_DIR=.*|OUTPUT_JSON_DIR=\"$ARCHIVE_DIR\"  # Folder on your webserver - adjust as needed|" "$SH_FILE"
else
  sed -i "1i OUTPUT_JSON_DIR=\"$ARCHIVE_DIR\"  # Folder on your webserver - adjust as needed" "$SH_FILE"
fi
echo -e "${GREEN}Configured OUTPUT_JSON_DIR in $SH_FILE to $ARCHIVE_DIR${NORMAL}"

# Set ownership for webroot files
echo -e "${YELLOW}Setting ownership of $TARGET_DIR to www-data:www-data...${NORMAL}"
chown -R www-data:www-data "$TARGET_DIR"

# Offer to setup daily cronjob for the shell script
echo -e "${YELLOW}Do you want to set up a daily cronjob to run the Fail2Ban log JSON exporter script? (Y/N)${NORMAL}"
read -r SET_CRON

if [[ "${SET_CRON,,}" == "y" ]]; then
  CRON_JOB="0 0 * * * $SH_SCRIPT_PATH/fail2ban_log2json.sh > /dev/null 2>&1"
  
  # Check if cronjob exists already
  crontab -l 2>/dev/null | grep -F "$SH_SCRIPT_PATH/fail2ban_log2json.sh" >/dev/null
  if [ $? -eq 0 ]; then
    echo -e "${BLUE}Cronjob already exists. Skipping adding.${NORMAL}"
  else
    # Add cronjob
    (crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -
    echo -e "${GREEN}Daily cronjob added:${NORMAL} $CRON_JOB"
  fi
else
  echo -e "${YELLOW}To setup the cronjob manually later, run this command:${NORMAL}"
  echo "0 0 * * * $SH_SCRIPT_PATH/fail2ban_log2json.sh > /dev/null 2>&1"
fi

# Clean up: remove .sh from repo if copied to separate location
if [ "$SH_SCRIPT_PATH" != "$TARGET_DIR" ]; then
  if [ -f "$TARGET_DIR/fail2ban_log2json.sh" ]; then
    rm "$TARGET_DIR/fail2ban_log2json.sh"
    echo -e "${GREEN}Removed original fail2ban_log2json.sh from webroot repo to avoid duplicates.${NORMAL}"
  fi
fi

echo -e "${GREEN}Installation complete!${NORMAL}"
echo -e "You can now open the Fail2Ban-Report web UI from your web server root folder: $TARGET_DIR"
echo -e "Remember to configure your webserver's .htaccess or equivalent security settings as needed."
