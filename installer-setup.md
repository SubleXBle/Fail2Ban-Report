# Fail2Ban-Report Installer Setup Guide

## Introduction

This document explains the interactive steps and choices presented to you during the Fail2Ban-Report automatic installation process. It also describes what the installer does behind the scenes to set up the tool properly.

---

## Installer Interactive Questions and Options

### 1. Web Root Directory

- **Prompt:**  
  `Please enter the installation directory for the web tool (default: /var/www/html):`

- **Purpose:**  
  This is where the web interface files of Fail2Ban-Report will be installed.  
  Press **Enter** to accept the default or specify a custom path if your web server uses a different document root.

---

### 2. Shell Scripts Directory

- **Prompt:**  
  `Please enter the directory to store shell scripts (default: /opt/Fail2Ban-Report):`

- **Purpose:**  
  The two main shell scripts (`fail2ban_log2json.sh` and `firewall-update.sh`) will be copied here.  
  This directory should be outside the web root for security reasons.  
  Press **Enter** to accept the default or specify a custom path.

---

### 3. Git Availability and Download Method

- The installer checks if `git` is installed.

- If **git is not found**, you will be asked:  
  `Would you like to download the repository as a ZIP archive using wget instead? (Y/N):`

- If you answer **yes**, the installer will download and extract the ZIP archive using `wget` and `unzip`.  
- If you answer **no**, the installation will abort because one of these methods is required.

---

### 4. jq Installation

- The installer checks if the JSON processor tool `jq` is installed.

- If **not found**, you will be prompted:  
  `Would you like to install jq now? (Y/N):`

- If you choose **yes**, the script attempts to install `jq` using your package manager (`apt`, `dnf`, or `pacman`).  
- If you choose **no** or your package manager is unsupported, installation will stop, since `jq` is required.

---

### 5. AbuseIPDB Reputation Reporting (Optional)

- You will see an informational message about the AbuseIPDB feature that allows IP reputation lookups.

- You will be asked:  
  `Would you like to enable AbuseIPDB support? (Y/N):`

- If **yes**, you will be prompted to enter your AbuseIPDB API key:  
  `Please enter your AbuseIPDB API key (or leave blank to add later):`

- If you enter a key, it will be saved in the config automatically.  
- If left blank, the config will enable reporting but without an API key; you must add it manually later.

- If you answer **no**, the config file will disable reporting completely.

---

### 6. Cronjob Installation for fail2ban_log2json.sh

- The installer will ask:  
  `Install daily cronjob for fail2ban_log2json.sh at 3 AM? (Y/N):`

- If **yes**, a daily cronjob running at 3 AM will be created.  
- If **no**, no cronjob is installed for this script.

---

### 7. Cronjob Installation for firewall-update.sh

- You will be asked to select how often to run the firewall update script:

  Please select how often the firewall-update.sh cronjob should run:

```
Every 5 minutes

Every 15 minutes

Do not install cronjob
Enter your choice [1-3]:
```

- Based on your choice, the cronjob will be installed or skipped.

---

## What the Installer Does

- **Repository Setup:**  
Clones or downloads the Fail2Ban-Report repository into the chosen web root directory.

- **Dependency Handling:**  
Checks for `jq` and installs it if requested.

- **Script Deployment:**  
Copies `fail2ban_log2json.sh` and `firewall-update.sh` into the chosen scripts directory, sets executable permissions, and configures internal paths.

- **Config File Generation:**  
Creates a `.config` file in the scripts directory to enable or disable AbuseIPDB reporting, including your API key if provided.

- **Permissions:**  
Sets ownership of the web directory files to `www-data:www-data`.

- **Cleanup:**  
Removes shell scripts from the web directory for security and deletes the installer script after successful completion.

- **Cronjob Setup:**  
Adds cronjobs for automatic execution of the log parser and firewall updater, based on your choices.

---

## Important Notes

- The shell scripts **must not** be located inside the web root for security reasons.

- Cronjobs involving firewall updates should run with root privileges.

- You need to manually secure the web directory by configuring the `.htaccess` file as included in the repository.

- The AbuseIPDB feature requires a valid API key for functionality.

---

## After Installation

- ⚠️ Make sure that the directory is inacessible for others ⚠️

- Verify that your web server serves the Fail2Ban-Report directory correctly.

- Access the Fail2Ban-Report web interface via your browser.

- Confirm that cronjobs are running and updating the data and firewall rules as expected.

---

Thank you for choosing Fail2Ban-Report!
