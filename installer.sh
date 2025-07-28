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
TARGET_DIR="${WEBROOT%/}/Fail2Ban-Report"

# Ask for .sh script storage path
read -rp "Enter path where shell scripts should be stored (default: $DEFAULT_SH_PATH): " SH_PATH
SH_PATH=${SH_PATH:-$DEFAULT_SH_PATH}

echo -e "Using webroot installation path: $TARGET_DIR"
echo -e "Using shell script path: $SH_PATH"

# Check for git
echo -e "${BLUE}Checking if git is installed...${NORMAL}"
if ! command -v git &>/dev/null; then
  echo -e "${RED}Git not found. Please install git and rerun the installer.${NORMAL}"
  exit 1
fi

# Check and install jq if missing
echo -e "${BLUE}Checking for jq...${NORMAL}"
if ! command -v jq &>/dev/null; then
  echo -e "${YELLOW}jq not found. Installing jq...${NORMAL}"
  if command -v apt &>/dev/null; then
    sudo apt update && sudo apt install -y jq
  elif command -v dnf &>/dev/null; then
    sudo dnf install -y jq
  elif command -v pacman &>/dev/null; then
    sudo pacman -Sy jq --noconfirm
  else
    echo -e "${RED}Package manager not supported. Please install jq manually.${NORMAL}"
    exit 1
  fi
else
  echo -e "${GREEN}jq is installed.${NORMAL}"
fi

# Clone or update repo
if [ ! -d "$TARGET_DIR" ]; then
  echo -e "${YELLOW}Cloning Fail2Ban-Report repository into $TARGET_DIR...${NORMAL}"
  git clone -b "$BRANCH_NAME" "$REPO_URL" "$TARGET_DIR"
else
  echo -e "${BLUE}Repository already exists. Pulling latest changes...${NORMAL}"
  cd "$TARGET_DIR" || { echo -e "${RED}Cannot cd to $TARGET_DIR${NORMAL}"; exit 1; }
  git pull origin "$BRANCH_NAME"
fi

# Ensure shell script path exists
mkdir -p "$SH_PATH"

# === fail2ban_log2json.sh ===
if [ -f "$TARGET_DIR/fail2ban_log2json.sh" ]; then
  echo -e "${BLUE}Installing fail2ban_log2json.sh...${NORMAL}"
  cp "$TARGET_DIR/fail2ban_log2json.sh" "$SH_PATH/"
  chmod +x "$SH_PATH/fail2ban_log2json.sh"
else
  echo -e "${RED}fail2ban_log2json.sh not found in repo.${NORMAL}"
  exit 1
fi

# Set ARCHIVE_PATH in fail2ban_log2json.sh
ARCHIVE_PATH="${TARGET_DIR}/archive"
ESCAPED_ARCHIVE_PATH=$(echo "$ARCHIVE_PATH" | sed 's_/_\\/_g')
if grep -q "^ARCHIVE_PATH=" "$SH_PATH/fail2ban_log2json.sh"; then
  sed -i "s/^ARCHIVE_PATH=.*/ARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"/" "$SH_PATH/fail2ban_log2json.sh"
else
  sed -i "1iARCHIVE_PATH=\"$ESCAPED_ARCHIVE_PATH\"" "$SH_PATH/fail2ban_log2json.sh"
fi

mkdir -p "$ARCHIVE_PATH"
chmod 755 "$ARCHIVE_PATH"

# === firewall-update.sh ===
if [ -f "$TARGET_DIR/firewall-update.sh" ]; then
  echo -e "${BLUE}Installing firewall-update.sh...${NORMAL}"
  cp "$TARGET_DIR/firewall-update.sh" "$SH_PATH/"
  chmod +x "$SH_PATH/firewall-update.sh"

  # Set correct blocklist.json path in firewall-update.sh
  ESCAPED_BLOCKLIST_PATH=$(echo "$ARCHIVE_PATH/blocklist.json" | sed 's_/_\\/_g')
  sed -i "s|^BLOCKLIST_JSON=.*|BLOCKLIST_JSON=\"$ESCAPED_BLOCKLIST_PATH\"|" "$SH_PATH/firewall-update.sh"
else
  echo -e "${RED}firewall-update.sh not found in repo.${NORMAL}"
fi

# Set ownership to www-data
echo -e "${BLUE}Setting ownership of $TARGET_DIR to www-data:www-data...${NORMAL}"
chown -R www-data:www-data "$TARGET_DIR"

# Remove duplicate script in repo
rm -f "$TARGET_DIR/fail2ban_log2json.sh"

# .htaccess Hinweis
echo -e "${BLUE}\nIMPORTANT: Configure your .htaccess to secure the application.${NORMAL}"
echo "Example .htaccess is included in the repo."

# Ask about fail2ban_log2json cronjob
read -rp "Install daily cronjob for fail2ban_log2json.sh at 3 AM? (Y/N): " INSTALL_CRON
INSTALL_CRON=${INSTALL_CRON,,}
if [[ "$INSTALL_CRON" == "y" ]]; then
  CRON_CMD="0 3 * * * $SH_PATH/fail2ban_log2json.sh > /dev/null 2>&1"
  (crontab -l 2>/dev/null | grep -v -F "$SH_PATH/fail2ban_log2json.sh"; echo "$CRON_CMD") | crontab -
  echo -e "${GREEN}Cronjob installed: $CRON_CMD${NORMAL}"
fi

# Ask about firewall-update.sh cronjob
read -rp "Install firewall-update.sh to run every 5 minutes via cron? (Y/N): " INSTALL_FW_CRON
INSTALL_FW_CRON=${INSTALL_FW_CRON,,}
if [[ "$INSTALL_FW_CRON" == "y" ]]; then
  FW_CRON_CMD="*/5 * * * * $SH_PATH/firewall-update.sh > /dev/null 2>&1"
  (crontab -l 2>/dev/null | grep -v -F "$SH_PATH/firewall-update.sh"; echo "$FW_CRON_CMD") | crontab -
  echo -e "${GREEN}Firewall cronjob installed: $FW_CRON_CMD${NORMAL}"
fi

echo -e "${GREEN}\nInstallation completed successfully!${NORMAL}"
echo "Webroot path: $TARGET_DIR"
echo "Shell scripts in: $SH_PATH"
echo "Firewall script path: $SH_PATH/firewall-update.sh"
echo
echo "Make sure to adjust webserver config and test the blocklist system."
