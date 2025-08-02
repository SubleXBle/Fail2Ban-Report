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
DEFAULT_CONFIG_PATH="$DEFAULT_SH_PATH/fail2ban-report.config"
PHP_CONFIG_PATH="$DEFAULT_SH_PATH/includes/config.php"

echo -e "${BLUE}--- Fail2Ban-Report Installer ---${NORMAL}"

# Ask for Webroot path
read -rp "Please enter the installation directory for the web tool (default: $DEFAULT_WEBROOT): " WEBROOT
WEBROOT=${WEBROOT:-$DEFAULT_WEBROOT}
TARGET_DIR="${WEBROOT%/}/Fail2Ban-Report"

# Ask for .sh script storage path
read -rp "Please enter the directory to store shell scripts (default: $DEFAULT_SH_PATH): " SH_PATH
SH_PATH=${SH_PATH:-$DEFAULT_SH_PATH}

# Ask for config file path (optional)
read -rp "Please enter the path for the config file (default: $DEFAULT_CONFIG_PATH): " CONFIG_PATH
CONFIG_PATH=${CONFIG_PATH:-$DEFAULT_CONFIG_PATH}

echo -e "\nUsing webroot installation path: $TARGET_DIR"
echo -e "Using shell script path: $SH_PATH"
echo -e "Using config file path: $CONFIG_PATH\n"

# Check for git with polite option for wget fallback
echo -e "${BLUE}Checking if git is installed...${NORMAL}"
if ! command -v git &>/dev/null; then
  echo -e "${YELLOW}Git is not installed.${NORMAL}"
  read -rp "Would you like to download the repository as a ZIP archive using wget instead? (Y/N): " use_wget
  use_wget=${use_wget,,}
  if [[ "$use_wget" == "y" ]]; then
    ZIP_URL="https://github.com/SubleXBle/Fail2Ban-Report/archive/refs/heads/$BRANCH_NAME.zip"
    ZIP_FILE="/tmp/fail2ban_report.zip"
    echo -e "${BLUE}Downloading ZIP archive...${NORMAL}"
    if ! command -v wget &>/dev/null; then
      echo -e "${RED}wget is not installed. Please install wget or git manually and rerun this installer.${NORMAL}"
      exit 1
    fi
    wget -O "$ZIP_FILE" "$ZIP_URL"
    echo -e "${BLUE}Extracting ZIP archive...${NORMAL}"
    if ! command -v unzip &>/dev/null; then
      echo -e "${RED}unzip is not installed. Please install unzip manually and rerun this installer.${NORMAL}"
      exit 1
    fi
    unzip -o "$ZIP_FILE" -d /tmp/
    rm -f "$ZIP_FILE"
    mv "/tmp/Fail2Ban-Report-$BRANCH_NAME" "$TARGET_DIR"
  else
    echo -e "${RED}Git is required or please choose to download via wget. Exiting.${NORMAL}"
    exit 1
  fi
else
  # git is installed, proceed with clone or pull
  if [ ! -d "$TARGET_DIR" ]; then
    echo -e "${YELLOW}Cloning Fail2Ban-Report repository into $TARGET_DIR...${NORMAL}"
    git clone -b "$BRANCH_NAME" "$REPO_URL" "$TARGET_DIR"
  else
    echo -e "${BLUE}Repository already exists. Pulling latest changes...${NORMAL}"
    cd "$TARGET_DIR" || { echo -e "${RED}Cannot change directory to $TARGET_DIR${NORMAL}"; exit 1; }
    git pull origin "$BRANCH_NAME"
  fi
fi

# Check and optionally install jq
echo -e "\n${BLUE}Checking for jq...${NORMAL}"
if ! command -v jq &>/dev/null; then
  echo -e "${YELLOW}jq is not installed.${NORMAL}"
  read -rp "Would you like to install jq now? (Y/N): " install_jq
  install_jq=${install_jq,,}
  if [[ "$install_jq" == "y" ]]; then
    if command -v apt &>/dev/null; then
      sudo apt update && sudo apt install -y jq
    elif command -v dnf &>/dev/null; then
      sudo dnf install -y jq
    elif command -v pacman &>/dev/null; then
      sudo pacman -Sy jq --noconfirm
    else
      echo -e "${RED}Unsupported package manager. Please install jq manually.${NORMAL}"
      exit 1
    fi
  else
    echo -e "${RED}jq is required for this tool. Exiting.${NORMAL}"
    exit 1
  fi
else
  echo -e "${GREEN}jq is already installed.${NORMAL}"
fi

# Ensure shell script path exists
mkdir -p "$SH_PATH"

# === fail2ban_log2json.sh installation ===
if [ -f "$TARGET_DIR/fail2ban_log2json.sh" ]; then
  echo -e "\n${BLUE}Installing fail2ban_log2json.sh...${NORMAL}"
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

# === firewall-update.sh installation ===
if [ -f "$TARGET_DIR/firewall-update.sh" ]; then
  echo -e "\n${BLUE}Installing firewall-update.sh...${NORMAL}"
  cp "$TARGET_DIR/firewall-update.sh" "$SH_PATH/"
  chmod +x "$SH_PATH/firewall-update.sh"

  # Set correct blocklist.json path in firewall-update.sh
  ESCAPED_BLOCKLIST_PATH=$(echo "$ARCHIVE_PATH/blocklist.json" | sed 's_/_\\/_g')
  sed -i "s|^BLOCKLIST_JSON=.*|BLOCKLIST_JSON=\"$ESCAPED_BLOCKLIST_PATH\"|" "$SH_PATH/firewall-update.sh"
else
  echo -e "${RED}firewall-update.sh not found in repo.${NORMAL}"
fi

# === Create PHP config include with config path constant ===
echo -e "\n${BLUE}Creating PHP config include file for config path...${NORMAL}"
mkdir -p "$(dirname "$PHP_CONFIG_PATH")"
cat > "$PHP_CONFIG_PATH" <<EOF
<?php
define('FAIL2BAN_CONFIG_PATH', '$CONFIG_PATH');
EOF
chmod 644 "$PHP_CONFIG_PATH"
echo -e "${GREEN}Created $PHP_CONFIG_PATH with config path constant.${NORMAL}"

# === AbuseIPDB and max_display_days config setup ===
echo -e "\n${BLUE}Configuring reporting and display settings...${NORMAL}"
echo "This feature lets you check IP reputation from the web interface using AbuseIPDB."
echo "To enable this, you'll need a (free) API key from https://www.abuseipdb.com/"
echo "If you don't have a key now, you can leave it blank and add it later manually."

read -rp "Would you like to enable AbuseIPDB support? (Y/N): " ENABLE_ABUSE
ENABLE_ABUSE=${ENABLE_ABUSE,,}

read -rp "How many days of daily reports should be shown in the main list? (default: 7): " MAX_DAYS
MAX_DAYS=${MAX_DAYS:-7}

if [[ "$ENABLE_ABUSE" == "y" ]]; then
  read -rp "Please enter your AbuseIPDB API key (or leave blank to add later): " API_KEY
  API_KEY=${API_KEY:-""}

  echo -e "${YELLOW}Creating AbuseIPDB-enabled config file...${NORMAL}"
  cat > "$CONFIG_PATH" <<EOF
[reports]
report=true
report_types=abuseipdb

[AbuseIPDB API Key]
abuseipdb_key=$API_KEY

[Fail2Ban-Daily-List-Settings]
max_display_days=$MAX_DAYS
EOF

  if [[ -z "$API_KEY" ]]; then
    echo -e "${YELLOW}Warning: No API key entered. Add your AbuseIPDB API key manually in $CONFIG_PATH to enable reporting.${NORMAL}"
  fi
else
  echo -e "${YELLOW}Creating config file with AbuseIPDB disabled...${NORMAL}"
  cat > "$CONFIG_PATH" <<EOF
[reports]
report=false
report_types=

[AbuseIPDB API Key]
abuseipdb_key=

[Fail2Ban-Daily-List-Settings]
max_display_days=$MAX_DAYS
EOF
fi

chmod 600 "$CONFIG_PATH"
echo -e "${GREEN}Config file created at $CONFIG_PATH${NORMAL}"

# Set ownership to www-data
echo -e "\n${BLUE}Setting ownership of $TARGET_DIR to www-data:www-data...${NORMAL}"
chown -R www-data:www-data "$TARGET_DIR"

# Remove fail2ban_log2json.sh from web directory (cleanup)
rm -f "$TARGET_DIR/fail2ban_log2json.sh"

# .htaccess Hinweis
echo -e "\n${BLUE}IMPORTANT: Please configure your .htaccess file to secure the application.${NORMAL}"
echo "An example .htaccess file is included in the repository."

# --- Cronjob installation for fail2ban_log2json.sh ---
read -rp $'\nInstall daily cronjob for fail2ban_log2json.sh at 3 AM? (Y/N): ' INSTALL_CRON
INSTALL_CRON=${INSTALL_CRON,,}
if [[ "$INSTALL_CRON" == "y" ]]; then
  CRON_CMD="0 3 * * * $SH_PATH/fail2ban_log2json.sh > /dev/null 2>&1"
  (crontab -l 2>/dev/null | grep -v -F "$SH_PATH/fail2ban_log2json.sh"; echo "$CRON_CMD") | crontab -
  echo -e "${GREEN}Cronjob installed: $CRON_CMD${NORMAL}"
else
  echo -e "${BLUE}Skipping cronjob installation for fail2ban_log2json.sh.${NORMAL}"
fi

# --- Cronjob installation for firewall-update.sh with choice ---
echo -e "\nPlease select how often the firewall-update.sh cronjob should run:"
echo "1) Every 5 minutes"
echo "2) Every 15 minutes"
echo "3) Do not install cronjob"

read -rp "Enter your choice [1-3]: " FW_CRON_CHOICE
case "$FW_CRON_CHOICE" in
  1)
    FW_CRON_SCHEDULE="*/5 * * * *"
    ;;
  2)
    FW_CRON_SCHEDULE="*/15 * * * *"
    ;;
  3)
    FW_CRON_SCHEDULE=""
    ;;
  *)
    echo -e "${YELLOW}Invalid choice. No cronjob will be installed for firewall-update.sh.${NORMAL}"
    FW_CRON_SCHEDULE=""
    ;;
esac

if [ -n "$FW_CRON_SCHEDULE" ]; then
  FW_CRON_CMD="$FW_CRON_SCHEDULE $SH_PATH/firewall-update.sh > /dev/null 2>&1"
  (crontab -l 2>/dev/null | grep -v -F "$SH_PATH/firewall-update.sh"; echo "$FW_CRON_CMD") | crontab -
  echo -e "${GREEN}Firewall cronjob installed: $FW_CRON_CMD${NORMAL}"
else
  echo -e "${BLUE}No firewall cronjob installed.${NORMAL}"
fi

echo -e "\n${GREEN}Installation completed successfully!${NORMAL}"
echo "Webroot path: $TARGET_DIR"
echo "Shell scripts in: $SH_PATH"
echo "Firewall script path: $SH_PATH/firewall-update.sh"
echo

# === Cleanup frontend install directory ===
echo -e "${BLUE}Cleaning up frontend installation directory...${NORMAL}"
find "$TARGET_DIR" -type f -name "*.sh" -exec rm -f {} \;
find "$TARGET_DIR" -type f -name "*.md" -exec rm -f {} \;
find "$TARGET_DIR" -type f -name "*.config" -exec rm -f {} \;

if [ -d "$TARGET_DIR/assets/images" ]; then
  rm -rf "$TARGET_DIR/assets/images"
  echo -e "${GREEN}Removed $TARGET_DIR/assets/images/${NORMAL}"
fi

# Remove this installer script itself
INSTALLER_PATH="$(realpath "$0")"
echo -e "${YELLOW}Removing installer script: $INSTALLER_PATH${NORMAL}"
rm -f "$INSTALLER_PATH"

echo "Please ensure your webserver configuration is properly adjusted and test the blocklist system."
