# Fail2Ban-Report

Fail2Ban Report is a privacy-friendly and self-hosted dashboard to monitor, manage, and extend your Fail2Ban bans via a web interface — including automated firewall integration and easy control of blocklists.

> Designed for sysadmins, self-hosters, and security-conscious users who want better insight into Fail2Ban activity and fine-grained control over IP blocking.

---

## 🛡️ This tool does not replace proper intrusion detection and access control. It is a visualization layer and should be deployed accordingly.

---

## 🚀 Features

- 📊 **Live overview** of Fail2Ban jails, ban history and active bans (depends on how often cronjobs run)
- 🔒 **Integrated blocklist system** with JSON-based state tracking
- 🔄 **Automatic firewall updates** (currently only via `ufw`, `nftables` planned for future release)
- 🌐 **Lightweight PHP web interface** (no database or frameworks required)
- 🧱 Compatible with hardened environments (strict HTTP headers, no external assets)
- 📁 Fully On-premises — no tracking, no cloud, no dependencies
- 🔧 **Installer script** included for quick setup
- 🛠 Easily extensible and modular by design

---

## 🖥️ Screenshots

![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-1.png)
![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-2.png)
![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-3.png)

---

## 🔐 Architecture & Security

Fail2Ban Report was built with simplicity, security, and control in mind:

- All data is local — no cloud, no external APIs, no tracking
- No cookies, no JavaScript required
- Access control via web server authentication (`.htaccess`)
- JSON-based blocklist structure: easy to audit, version, and edit
- Separation of concerns:
  - Web interface handles display and editing
  - Backend scripts manage interaction with firewall and fail2ban
- Security-hardened `.htaccess` and HTTP headers recommended (example included)

---

## 🧰 Installation

### 🔧 Option 1: Using the Installer (recommended)

Run the installer:

```
sudo wget https://raw.githubusercontent.com/SubleXBle/Fail2Ban-Report/main/Installer.sh && chmod +x Installer.sh && ./Installer.sh && rm Installer.sh
```

The installer will:
- Install required packages (`jq`, `ufw`, etc.)
- Place the files in `/var/www/html/fail2ban-report/` (or custom path)
- Set permissions correctly
- Schedule automatic firewall sync
- Configure the blocklist path

### 🛠 Option 2: Manual Installation

1. Install dependencies:

```
sudo apt install jq ufw
```

2. Copy the project files to your desired web root, e.g.:

```
/var/www/html/Fail2Ban-Report/
```

3. Set file permissions:

```
sudo chown -R www-data:www-data /var/www/html/fail2ban-report
sudo chmod -R 755 /var/www/html/fail2ban-report
```

4. Make sure `BLOCKLIST_JSON` path is set correctly in `firewall-update.sh` and `fail2ban_log2json.sh`.

5. (Optional) Add cronjob or systemd timer to run `firewall-update.sh` and `fail2ban_log2json.sh` regularly.

---

## 🖥️ Web Interface Usage

Open the web interface in your browser:

```
http://your-domain.tld/Fail2Ban-Report/
```

There you can:

- View all Fail2Ban jails
- See recent ban events
- View, activate or deactivate individual IP blocks
- Search and sort IPs
- Toggle firewall integration for each IP

Changes to blocklist state are automatically picked up by `firewall-update.sh`.

---

## 🔁 How Banning and Unbanning Works

### When a ban occurs:

1. Fail2Ban bans the IP and logs the event
2. `fail2ban-report` collects the event (via `fail2ban-client`)
3. The IP is added to the JSON blocklist with `"active": true`
4. `firewall-update.sh` ensures the IP is blocked via `ufw`

### When an IP is unbanned:

1. You uncheck `"active"` in the web UI or set `"active": false` manually
2. `firewall-update.sh` removes the `ufw` rule for the IP
3. The IP entry is cleaned up from JSON (if desired)

This ensures full synchronization between Fail2Ban, firewall, and the web interface.

---

## 🗺️ Roadmap

- ✅ Beta-2: UFW integration and JSON state sync (done!)
- ⏳ Beta-3: Support for nftables and firewalld
- ⏳ Beta-4: Optional SQLite history log
- ⏳ v5: Ban reason enrichment (e.g. from logs)
- ⏳ Web UI enhancements (sorting, filtering, statistics)
- ⏳ Multi-server sync support

---

## 🤝 Contributing

Pull requests and feedback are warmly welcome!

If you find a bug, have an idea, or want to contribute code, feel free to:

- Open an [Issue](https://github.com/YOUR_USERNAME/fail2ban-report/issues)
- Submit a [Pull Request](https://github.com/YOUR_USERNAME/fail2ban-report/pulls)

For larger features, feel free to start a discussion first.

---

## 📄 License

This project is licensed under the MIT License. See `LICENSE` for details.

---

Made with ❤️ by [SubleXBle](https://github.com/SubleXBle).

