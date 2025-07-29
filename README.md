# Fail2Ban-Report

A simple and clean web-based reporting tool for Fail2Ban events.
Turn your daily Fail2Ban logs into searchable and filterable JSON reports – perma ban IPs from this List directly and manage its Blocklist. Ban/Unban

> Designed for sysadmins, self-hosters, and security-conscious users who want better insight into Fail2Ban activity and fine-grained control over IP blocking. ⚠️ Firewall Actions work only with Fail2Ban right now ⚠️

---


## 🛡️ This tool does not replace proper intrusion detection and access control. It is a visualization layer and should be deployed accordingly.

#### ⚠️ For safety and clarity, Fail2Ban-Report only modifies firewall rules related to its own IP blocklist (blocklist.json). It never touches or overrides other firewall settings, ensuring compatibility with existing Fail2Ban jails and custom rules.

> This Tool will read the logfile from fail2ban and write ban and Unban Events to a .json file stored on a secured webspace. The Tool will show those Events in a List with an Action Button to perform Actions to the Listed IP Address (e.g. Block IP) The IP will the be written to another .json File (Blocklist) with an "active=true" state, so when the Firewall-Script runs the next time it will Add those IP Adresses on the blocklist.json to the Firewall to block them - so they will also get reapplyed when the Server restarts as soon as the Firewall-Script runs the next time.

> When you Show the Blocklist (Button on Top of Page) you see the IP Addresses that are in the blocklist.json and you can perform an unblock Action. This will set active=false in the blocklist.json and as soon as the firewall-script runs the next time, it will perform an unblock action on this IP Address in the Firewall and remove the IP from the blocklist.json

> So you have the .sh Scripts acting as a backend (gather information from source and perform actions on the system) and the Frontend Layer on your Webspace for visualisation.

> So this Tool gets the Events from Fail2Ban and handles its own blocklist to perform Block and Unblock Actions on UFW

⚠️ Firewall Actions work only with Fail2Ban right now ⚠️

---

## 📦 Features

- **Overview** of Fail2Ban, ban history and active bans (depends on how often cronjobs run)
- **Integrated blocklist system** with JSON-based state tracking
- **Automatic firewall updates** (currently only via `ufw` other Firewall Systems planned for future release)
- **Lightweight web interface** (no database or frameworks required)
- Compatible with hardened environments (strict HTTP headers, no external assets)
- **Installer script** included for quick setup
- Easily extensible by its modular by design
- **Logging of Block and Unblock Actions** by setting LOGGING=true in firewall-update.sh

---


## 🖥️ Screenshots

![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-1.png)

Main Window

![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-3.png)

new: Actions to Perform

![assets/images/Fail2Ban-Report-1.png](assets/images/Fail2Ban-Report-2.png)

new: Banlist manipulation


---

## 🔐 Architecture & Security

Fail2Ban Report was built with simplicity, security, and control in mind:

- All data is local — no cloud, no external APIs, no tracking
- Access control via web server authentication (`.htaccess`)
  - Security-hardened `.htaccess` and HTTP headers recommended (example included)
- JSON-based blocklist structure: easy to audit, version, and edit
- Separation of concerns:
  - Web interface handles display and editing
  - Backend scripts manage interaction with firewall and fail2ban

---


## ⚙️ Installation / Setup Instructions

### ⚠️ Installer will install Software on the system:
 - [`jq`](https://jqlang.org/)  - for easy processing of .json files.

### 🔧 Option 1: Using the Installer (experimental)

Run the installer:

```
sudo wget https://raw.githubusercontent.com/SubleXBle/Fail2Ban-Report/main/Installer.sh && chmod +x Installer.sh && ./Installer.sh && rm Installer.sh
```

The installer will:
- Install required packages (`jq`,)
- Place the files in `/var/www/html/fail2ban-report/` (or custom path)
- Set permissions correctly
- Schedule automatic firewall sync
- Configure the blocklist path

### 🛠 Option 2: Manual Installation

1. Install dependencies:

```
sudo apt install jq
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


This ensures "full synchronization" between Fail2Ban, firewall, and the web interface.

---

## 🪳 Bugfixing (a.k.a. cockroach control)

    ✅ Date Filter : will now show Events only from selected Date

---

## 🗺️ Roadmap & State of Project

Fail2Ban-Report is designed to be lightweight, modular, and open to future improvements. The following features are currently planned:

⚙️ Setup & Automation

    ✅ Setup script to automate initial installation, including directory structure and permissions
    ✅ Optionally auto-configure a daily cronjob
    ⏳ Make installer more robust

🔐 Security Features

    ✅ Integration of a stronger .htaccess file for basic access control and secure defaults
    ⏳ Make it even more secure and better (this will never get a check)

🔥 Active Defense Integration

    ✅ Allow manual IP blocking directly from the interface via ufw
    ⏳ Optionally enable automatic blocking of suspicious IPs based on defined criteria
    ⏳ add action for report to other Services (e.g. AbuseIPDB)
    ⏳ add support for nft iptables firewalld
    ⏳ multiple blocking of suspicious IPs at once

🌻 Beauty

    ⏳ Do some CSS Work to make it look nicer
    ⏳ Add more Filters (Date)

---

## 🤝 Contributing

Pull requests and feedback are warmly welcome!

If you find a bug, have an idea, or want to contribute code, feel free to:

- Open an [Issue](https://github.com/YOUR_USERNAME/fail2ban-report/issues)
- Submit a [Pull Request](https://github.com/YOUR_USERNAME/fail2ban-report/pulls)

For larger features, feel free to start a discussion first.

I'm happy to hear from users and contributors!
Whether it's:

    feature requests,
    improvement ideas,
    or even pull requests —
    Feel free to reach out or contribute directly.

If you use this tool and think "Hey, wouldn't it be cool if it could also do XYZ?" — I'm all ears!

---

## 📄 License

This project is licensed under the GPL3 License. See `LICENSE` for details.

---

Made with 🥵, 😿 and ❤️ by [SubleXBle](https://github.com/SubleXBle).

