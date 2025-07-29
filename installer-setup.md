# Fail2Ban-Report Automatic Installation Guide

## Overview

This installer script automates the setup of the Fail2Ban-Report tool by performing the following tasks:

- Cloning or downloading the repository  
- Installing required dependencies (`jq`)  
- Setting up shell scripts in a dedicated directory  
- Configuring file permissions and ownership  
- Optionally installing cronjobs for automated data processing and firewall updates  

---

## Prerequisites

- Linux server with Bash shell  
- `git` command (preferred) or fallback to `wget` and `unzip`  
- `jq` JSON processor utility  
- Sudo or root privileges for installation and configuration  
- Web server (e.g. Apache or Nginx) serving the chosen web directory  

---

## Installation Steps

1. **Run the Installer Script**

   Execute the installer script in your shell:

   bash install_fail2ban_report.sh

2. **Specify Installation Paths**

   When prompted:

   - Enter the web root directory where the Fail2Ban-Report web files will be installed (default: `/var/www/html`)  
   - Enter the directory to store shell scripts (default: `/opt/Fail2Ban-Report`)  

3. **Repository Setup**

   - If `git` is installed, the repository will be cloned or updated in the target web directory  
   - If `git` is not available, the installer offers to download a ZIP archive via `wget` and extract it  

4. **Dependency Installation**

   - The script checks for `jq` and offers to install it via your package manager (`apt`, `dnf`, or `pacman`)  
   - If declined or unsupported, installation will stop  

5. **Shell Scripts Deployment**

   - `fail2ban_log2json.sh` and `firewall-update.sh` are copied to the chosen shell script directory  
   - Executable permissions are set (`chmod +x`)  
   - Script variables are updated to point to the correct archive and blocklist paths  

6. **Permissions**

   - The web directory ownership is set to `www-data:www-data` recursively  

7. **Cleanup**

   - Shell scripts are removed from the web directory after copying  
   - The installer script removes itself after successful installation  

8. **Cronjobs**

   - You will be prompted to install a daily cronjob for `fail2ban_log2json.sh` (default 3 AM)  
   - You can select a schedule for `firewall-update.sh` (every 5 or 15 minutes or skip installation)  

---

## Notes

- Make sure your web server is configured properly to serve the Fail2Ban-Report directory  
- Adjust your `.htaccess` file for security; an example is included in the repository  
- Both shell scripts (`fail2ban_log2json.sh` and `firewall-update.sh`) should **not** reside in the web root for security reasons  
- Cronjobs must run as root for proper operation, especially for firewall updates  

---

## Usage

- Run the cronjobs regularly to keep your Fail2Ban-Report data and firewall rules updated automatically  
- Access the web interface at `https://yourdomain.tld/Fail2Ban-Report/` after installation  

---

Thank you for using Fail2Ban-Report!
