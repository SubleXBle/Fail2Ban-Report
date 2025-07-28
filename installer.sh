#!/bin/bash

# Colors for output
NORMAL='\033[0;39m'
GREEN='\033[1;32m'
RED='\033[1;31m'
YELLOW='\033[33m'
BLUE='\033[34m'

REPO_URL="https://github.com/SubleXBle/Fail2Ban-Report.git"
BRANCH_NAME="latest"

DEFAULT_WEBROOT="/var/www/html"
DEFAULT_SH_PATH="/opt/Fail2Ban-Report"

echo -e "${BLUE}--- Fail2Ban-Report Installer ---${NORMAL}"

# Ask for Webroot path
read -rp "Enter your webroot path where the tool should be installed (default: $DEFAULT_WEBROOT): " WEBROOT
WEBROOT=${WEBROOT:-$DEFAULT_WEBROOT}

# Full target directory where repo will be cloned (webroot + Fail2Ban-Report)
TARGET_DIR="${WEBROOT%/}/Fail2Ban-Report"

echo -e "Using webroot installation path: $TARGET_DIR"

# Ask for .sh script storage path
read -rp "Enter path where fail2ban_log2json.sh should be stored (default: $DEFAULT_SH_PATH): " SH_PATH
SH_PATH=${SH_PATH:-$DEFAULT_SH_PATH}
echo -e "Using shell script path: $SH_PATH"

# Check for git
echo -e "${BLUE}Checking if git is installed...${NORMAL}"
if ! command -v git &> /dev/null; then
  echo -e "${RED}Git not found. Please install git and rerun the installer.${NORMAL}"
  exit 1
fi

# Clone or update repo
if [ ! -d "$TARGET_DIR" ]; then
  echo -e "${YELLOW}Cloning Fail2Ban-Report repository into $TARGET_DIR...${NORMAL}"
  git clone -b "$BRANCH_NAME" "$REPO_URL" "$TARGET_DIR"
  if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to clone repository.${NORMAL}"
    exit 1
  fi
else
  echo -e "${BLUE}Repository already exists. Pulling latest changes...${NORMAL}"
  cd "$TARGET_DIR" || { echo -e "${RED}Cannot cd to $TARGET_DIR${NORMAL}"; exit 1; }
  git pull origin "$BRANCH_NAME"
  if [ $? -ne 0 ]; then
    echo -e "${RED}Failed to pull repository updates.${NORMAL}"
    exit 1
  fi
fi

# Create shell script target dir if needed
mkdir -p "$SH_PATH"

# Copy fail2ban_log2json.sh to SH_PATH and make executable
if [ -f "$TARGET_DIR/fail2ban_log2json.sh" ]; then
  echo -e "${BLUE}Copying fail2ban_log2json.sh to $SH_PATH...${NORMAL}"
  cp "$TARGET_DIR/fail2ban_log2json.sh" "$SH_PATH/"
  chmod +x "$SH_PATH/fail2ban_log2json.sh"
else
  echo -e "${RED}fail2ban_log2json.sh not found in repo, please check.${NORMAL}"
  exit 1
fi

# Set archive path inside the .sh script to point to the archive folder inside the webroot repo
ARCHIVE_PATH="${TARGET_DIR}/archive"
echo -e "${BLUE}Setting archive path in fail2ban_log2json.sh to $ARCHIVE_PATH ...${NORMAL}"

# Escape slashes for sed
ESCAPED_ARCHIVE_PATH=$(echo "$ARCHIVE_PATH" | sed 's_/_\\/_g')

if grep -q "^ARCHIVE_PATH=" "$SH_PATH/fail2ban_log2json.sh"; then
  sed -i "s/^ARCHIVE_PATH=.*/ARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"/" "$SH_PATH/fail2ban_log2json.sh"
else
  sed -i "1iARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"" "$SH_PATH/fail2ban_log2json.sh"
fi

# Create archive folder if missing
mkdir -p "$ARCHIVE_PATH"
chmod 755 "$ARCHIVE_PATH"

# Set permissions and ownership to www-data:www-data on the entire tool directory
echo -e "${BLUE}Setting ownership of $TARGET_DIR to www-data:www-data ...${NORMAL}"
chown -R www-data:www-data "$TARGET_DIR"

# Remove fail2ban_log2json.sh from repo folder to avoid duplicates
if [ -f "$TARGET_DIR/fail2ban_log2json.sh" ]; then
  echo -e "${BLUE}Removing fail2ban_log2json.sh from repo directory to avoid duplicates...${NORMAL}"
  rm "$TARGET_DIR/fail2ban_log2json.sh"
fi

# Inform about .htaccess setup
echo -e "${BLUE}\nIMPORTANT: Configure your .htaccess in the webroot to secure the application.${NORMAL}"
echo "Example .htaccess is included in the repo."

# Ask about cronjob setup
read -rp "Do you want to install a daily cronjob for fail2ban_log2json.sh at 3 AM? (Y/N): " INSTALL_CRON
INSTALL_CRON=${INSTALL_CRON,,}

CRON_CMD="0 3 * * * $SH_PATH/fail2ban_log2json.sh > /dev/null 2>&1"

if [[ "$INSTALL_CRON" == "y" ]]; then
  (crontab -l 2>/dev/null | grep -v -F "$SH_PATH/fail2ban_log2json.sh"; echo "$CRON_CMD") | crontab -
  echo -e "${GREEN}Cronjob installed:${NORMAL} $CRON_CMD"
else
  echo -e "${YELLOW}Skipping cronjob setup.${NORMAL}"
  echo "To set it up manually, add this line to your crontab:"
  echo "$CRON_CMD"
fi

echo -e "${GREEN}\nInstallation completed successfully!${NORMAL}"
echo "Webroot path: $TARGET_DIR"
echo "Shell script path: $SH_PATH/fail2ban_log2json.sh"
echo
echo "Remember to adjust your webserver config and secure the web directory properly."
echo "Happy selfhosting! 🚀"
