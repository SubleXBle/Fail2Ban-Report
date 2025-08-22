# Fail2Ban-Report
> Beta 5.0 | Version 0.5.0

> A lightweight web-based multi-server dashboard that transforms daily Fail2Ban logs into searchable and filterable JSON reports, while also providing centralized UFW IP blocklist management across all your servers.

**Integration**
>Designed for easy integration on a wide range of Linux systems — from small Raspberry Pis to modest business setups — though it’s not (yet) targeted at large-scale enterprise environments.
High flexibility comes from the backend shell scripts, which you can adapt to your specific environment or log sources to provide the JSON data the web interface needs (daily JSON event files).


🛡️ **Note**: This tool is a visualization and management layer — it does **not** replace proper intrusion detection or access control. Deploy it behind IP restrictions or HTTP authentication.

🔐 Security Notice

**Current Status:**  
> Fail2Ban-Report currently manages bans and unbans via UFW, providing a safe and persistent solution.
It does not modify Fail2Ban jails or existing Fail2Ban configurations directly, instead using UFW for its own "permanent jails".

> Version 0.5.0 introduces multi-server support and role-based access: Viewer accounts are read-only, while Admins can manage bans/unbans and blocklists across all connected servers via the dashboard.

**Future Direction:**  
> A potential long-term enhancement could include **direct interaction with Fail2Ban jails** — for example, user-controlled bans and unbans per jail.  
The existing structured `*.blocklist.json` format is already designed to support this, ensuring that any future manual ban management can remain "persistent", reviewable, and fully auditable.

Please read the [Installation Instructions](Setup-Instructions.md) carefully and secure your deployment with the provided `.htaccess`.
> still a little experimental feature : Use the Installer ![Installer Setup Documentation](installer-setup.md) It would be great if you tell me if the installer worked for your needs.

---

## 📚 What It Does
Fail2Ban-Report parses your `fail2ban.log` and generates JSON-based reports viewable via a responsive web dashboard.  
It provides optional tools to:  

- 📊 Visualize **ban** and **unban** events, including per-jail statistics  
- ⚡ Interact with IPs (e.g., manually block, unblock) — **only Admins can perform actions**  
- 📂 Maintain **jail-specific and per-server** persistent blocklists (JSON) with `active`, `pending`, and `source` metadata  
- 🔄 Sync those lists with your system firewall using **ufw**  
- 🌐 Switch between multiple servers in the dashboard for multi-server setups  
- 🚨 Show **Warning and Critical indicators** when ban rates exceed configurable thresholds  
- 🚨 Show **Markers** when an IP Address had a **ban-increase** (🟡) or **more than one ban event** on one day (🔴)

> **Note:** Viewer accounts are read-only. Direct integration with other firewalls or native Fail2Ban jail commands is not yet implemented.  


---

## 🧱 Architecture Overview

- **Backend Shell Scripts**:  
  - Parse logs and generate daily JSON event files  
  - Maintain and update `*.blocklist.json` per server  
  - Apply or remove firewall rules based on blocklist entries (`ufw`)  
  - Support for multi-server environments (future: rsync backend)  

- **Frontend Web Interface**:  
  - Displays event timelines, statistics, and per-jail blocklists  
  - Allows **multi-selection** for bulk ban/report actions  
  - Shows **pending status** for unprocessed manual actions  
  - Displays real-time warning indicators  
  - **Server switching**: choose which server’s data to view  
  - **Authentication**: Viewer (read-only) / Admin (Ban/Unban)  

- **JSON Blocklists**:  
  - Stored per jail and per server  
  - Contain IP entries with metadata (`active`, `pending`, timestamps, jail name, source)  
  - Only admins can modify entries (block/unblock) 

---

## 📦 Features

🔍 **Searchable & filterable log reports** — by date, jail, IP  
🔧 **Integrated JSON blocklist** — persistent Block-Overview per server  
🧱 **Firewall sync** — UFW supported
⚡ **Lightweight setup** — no DB, no frameworks  
🔐 **Secure & hardened** — minimal external dependencies (jq, awk), strict headers, htaccess protected  
🛠️ **Installer / Setup scripts** — automate folder creation, permissions, user management  
🧩 **Modular & extendable design** — includes, paths, scripts clearly separated  
🪵 **Optional Backend logging** — block/unblock actions logged via firewall-update.sh  
🕵️ **Optional IP reputation check** — AbuseIPDB manual lookup from UI  
🕵️ **Optional IP location/provider Check** — IP-Info manual lookup from UI
👥 **User roles & authentication** — Viewer (read-only) / Admin (Ban/Unban)  
🌐 **Multiserver support** — switch between servers in UI, central blocklist management

> 🧰 Works even on small setups (Raspberry Pi, etc.)

---

## 👥 Discussions

> If you want to join the conversation or have questions or ideas, visit the 💬 [Discussions page](https://github.com/SubleXBle/Fail2Ban-Report/discussions).

---


## 🆕 What's New in V 0.5.0

- ✅ Archive/ restructured → separated per server and "department" (fail2ban / blocklists)
- ✅ /opt/Fail2Ban-Report/ reorganized → cleaner separation of configs and scripts
- ✅ Centralized path management → reduced hardcoding, clearer structure
- ✅ Multi-server dashboard → switch between servers seamlessly
- ✅ Authentication → login with session, only admins can ban/unban
- ✅ User management script (.sh) → manage Fail2Ban-Report User-List
- ✅ User groups → Viewer (read-only) / Admin (ban/unban)
- ⏳ Working on the Sync-Backend
  - ✅ Syncing Eventlists
  - ⏳ Update Update-List on Block and Unblock
  - ⏳ Client get update list
  - ⏳ client snyc blocklists
- ⏳ rework installer
- ⏳ documentation
- ⏳ how 2 stuff

---

### ⚠️ Upgrade Notice

If you're upgrading from an existing installation 

> here will be added some new stuff

---


## 📄 Changelog

Details about all new features, improvements, and changed files can be found in the [Changelog](changelog.md).

This is especially useful if you want to manually patch or update individual files.


---

## 🪳 Bugfixes (History)

> - Found a bug? → [Open an issue](https://github.com/SubleXBle/Fail2Ban-Report/issues)

- ✅ **Date filter** now correctly limits displayed events (0.1.2)
- ✅ **Jail filter** now correctly shows only the jails present in the displayed event list. (0.2.1)
- ✅ **File date filtering** fix to include today's JSON logs and ensure latest files are listed correctly. (0.2.2)
- ✅ **Blocklist Path on unblocking** fixed a possible bug that could lead to not finding the blocklist.json when unblocking from the Blocklist view. (0.2.2)
  → Hotfixed on 05.08.2025 at 13:10 (UTC+2) directly in latest (0.2.3)
- ✅ **Installer** should now ask if you want to delete and reclone repo when allready existing (0.3.1)
- ✅ **Added FLOCK** to lock json files to not loose data when several write processes write at the same time (0.3.2)

---

## 🛣️ Roadmap
> I gave up the usual Roadmap - to have more freedom with development - Things like Multiserver was never on the Roadmap but allways in my mind.
> Using Fail2Ban-Report since it exists i have got some different views in terms of what Fail2Ban-Report is and how I think about what this project can become with a little time and polish.
> Suggestions and Ideas still welcome at any time (see Discussions)
> When you are using Fail2Ban-Report and you think "I would need to see .. " tell me, I am happy to see your Ideas!

## 👀 Outlook
- 📦 Further Improvements & Security Enhancements

---

## 🖼️ Screenshots

![screenshots/Header-050-1.png](screenshots/Header-050-1.png)

New Header with Login/Logout and Server-Chooser - grey text when not logged in


![screenshots/Header-050-2.png](screenshots/Header-050-2.png)

Yellow Text when logged in user


![screenshots/List-050-1.png](screenshots/List-050-1.png)

Fail2Ban - Event List


![screenshots/Block-List-050-1.png](screenshots/Block-List-050-1.png)

Blocklist


![screenshots/Message-Toast-050-1.png](screenshots/Message-Toast-050-1.png)

Message Toaster


![screenshots/Message-Toast-050-2.png](screenshots/Message-Toast-050-2.png)

New Feature : Block and Unblock Actions only for logged in admin role


---

## 🖥️ Demo
👀 Want to try out the look & feel?
There's a simple demo version available here – no backend, no real data:
👉 https://demo.suble.net/ 🔗

---

## ✅ What It Is
- A **role-based web dashboard** for Fail2Ban events: read-only for Viewers, action-enabled for Admins  
- A tool to **visualize** bans/unbans and **manually** manage blocked IPs  
- A **log parser + JSON generator** that works alongside your existing Fail2Ban setup  
- A way to **sync a persistent, per-jail blocklist** with your firewall (currently **UFW only**)  
- Supports **multi-server setups**, allowing you to switch between servers in the dashboard  
- Designed for **sysadmins** who want quick insights without SSH-ing into the server  

## ❌ What It Is Not
- ❌ A replacement for **Fail2Ban** itself (it depends on Fail2Ban)  
- ❌ A real-time IDS/IPS (data updates depend on log parsing intervals)  
- ❌ A universal firewall manager (no native support for iptables/nftables, etc. — yet)  
- ❌ A tool for **automatic** jail management (manual actions only for now)  
- ❌ A heavy analytics platform — it’s lightweight and log-driven by design


---

## 🤝 Contributing

Pull requests, feature ideas and bug reports are very welcome!

- Found a bug? → [Open an issue](https://github.com/SubleXBle/Fail2Ban-Report/issues)
- Want to contribute? → Fork and submit a pull request
- Have an idea? → Start a discussion or reach out directly : visit the 💬 [Discussions page](https://github.com/SubleXBle/Fail2Ban-Report/discussions)

> 💡 “Wouldn’t it be cool if it could also do XYZ?”  
> Absolutely — I’m happy to hear your ideas.

---


## 📄 License

This project is licensed under the **GPLv3**.  
Feel free to use, modify and share — but please respect the license terms.
