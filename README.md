# Fail2Ban-Report

A simple and clean web-based dashboard to turn your daily Fail2Ban logs into searchable and filterable JSON reports — with optional IP blocklist management for UFW.

🛡️ **Note**: This tool is a visualization and management layer — it does **not** replace proper intrusion detection or access control. Deploy it behind IP restrictions or HTTP authentication.

⚠️ **Security Notice**: Fail2Ban-Report modifies only its own blocklist (`blocklist.json`). It never touches existing Fail2Ban jails or unrelated firewall rules.  
Please read the [Installation Instructions](Setup-Instructions.md) carefully and secure your deployment with the provided `.htaccess`.
> experimental feature : Use the Installer

---

## 📚 What It Does

Fail2Ban-Report parses your `fail2ban.log` and generates JSON-based reports viewable via a web dashboard. It adds optional tools to:

- Visualize ban/unban events
- Interact with IPs (e.g. manually block/unblock)
- Maintain a persistent `blocklist.json`
- Sync that list with your system firewall (via `ufw` (other Firewalls than UFW not yet supported))

🧱 The architecture:
- **Backend Shell Scripts**: Parse logs, write JSON, and update UFW accordingly to `blocklist.json`
- **Frontend Web Interface**: Visualizes data and offers action buttons
- **JSON Blocklist**: Stores manually blocked IPs (`active=true`)

---

## 📦 Features

- 🔍 **Searchable + filterable** log reports (date, jail, IP)
- 🔧 **Integrated JSON blocklist** with action buttons
- 🧱 **Firewall sync** using UFW (planned: nftables, firewalld)
- ⚡ **Lightweight setup** — no DB, no frameworks
- 🔐 **Compatible with hardened environments** (no external assets, strict headers)
- 🛠️ **Installer script** to automate setup and permissions
- 🧩 **Modular design** for easy extension
- 🪵 Optional logging of block/unblock actions

> 🧰 Works even on small setups (Raspberry Pi, etc.)

---

## 🆕 What's New in V2

- Modular folder structure (split backend/frontend)
- Action buttons: Block/Unblock IPs directly
- Blocklist viewer: shows all manually blocked IPs
- `firewall-update.sh`: new script to apply block/unblock actions via `ufw`
- Action checkboxes to select multiple IP actions at once
- New `.htaccess` for secure deployments

---

## 🪳 Bugfixes

> - Found a bug? → [Open an issue](https://github.com/SubleXBle/Fail2Ban-Report/issues)

- ✅ **Date filter** now correctly limits displayed events
- ✅ **Jail filter** only shows existing jails from logs
- ⏳ **Report action** (planned feature – not yet implemented)

---

## 🛣️ Roadmap

### 🔧 Setup & Automation
- 🧩 Automated installer script 
- ✅ Optional cron setup for log parsing and firewall sync
- ⏳ More robust installer
- ⏳ Secure-by-default deployments

### 🔐 Security
- ✅ Hardened `.htaccess` with best practices
- ⏳ Further improvements (ongoing goal)

### 🔥 Active Defense
- ✅ Manual IP blocking via UI
- ⏳ Support for nftables, firewalld
- ⏳ Bulk blocking of multiple IPs
- ⏳ Optional automatic blocking based on patterns or thresholds
- ⏳ Integration with external services (e.g. AbuseIPDB reporting)

### 🌿 User Interface
- ⏳ Improve CSS and styling

---

## 🖼️ Screenshots

![assets/images/Fail2Ban-Report-B2.png](assets/images/Fail2Ban-Report-B2.png)
![assets/images/Fail2Ban-Report-BL.png](assets/images/Fail2Ban-Report-BL.png)
![assets/images/banip.png](assets/images/banip.png)

---

## 🤝 Contributing

Pull requests, feature ideas and bug reports are very welcome!

- Found a bug? → [Open an issue](https://github.com/SubleXBle/Fail2Ban-Report/issues)
- Want to contribute? → Fork and submit a pull request
- Have an idea? → Start a discussion or reach out directly

> 💡 “Wouldn’t it be cool if it could also do XYZ?”  
> Absolutely — I’m happy to hear your ideas.

---

## 📄 License

This project is licensed under the **GPLv3**.  
Feel free to use, modify and share — but please respect the license terms.
