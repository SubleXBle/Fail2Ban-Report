# Fail2Ban-Report
![Fail2Ban-Report Beta](https://img.shields.io/badge/Fail2Ban--Report%20🕵️-Beta_V0.5.0-gold?style=flat&logoColor=black)

![Fail2Ban](https://img.shields.io/badge/Fail2Ban-v1.7.0-blue?style=flat) ![UFW](https://img.shields.io/badge/UFW-Active-brightgreen?style=flat)


> A lightweight web-based multi-server dashboard that transforms daily Fail2Ban logs into searchable and filterable JSON reports, while also providing centralized UFW IP blocklist management across all your servers.

**Integration**
>Designed for easy integration on a wide range of Linux systems — from small Raspberry Pis to modest business setups — though it’s not (yet) targeted at large-scale enterprise environments.
High flexibility comes from the backend shell scripts, which you can adapt to your specific environment or log sources to provide the JSON data the web interface needs (daily JSON event files).


## 🛡️ **Note**: This tool is a visualization and management layer — it does **not** replace proper intrusion detection or access control. Deploy it behind IP restrictions or HTTP authentication only!

🔐 Security Notice

**Current Status:**  
> Fail2Ban-Report currently manages bans and unbans via UFW, providing a safe and persistent solution.
It does not modify Fail2Ban jails or existing Fail2Ban configurations directly, instead using UFW for its own "permanent jails".

> Version 0.5.0 introduces multi-server support and role-based access: Viewer accounts are read-only, while Admins can manage bans/unbans and blocklists across all connected servers via the dashboard.

**Future Direction:**  
> A potential long-term enhancement could include **direct interaction with Fail2Ban jails** — for example, user-controlled bans and unbans per jail.  
The existing structured `*.blocklist.json` format is already designed to support this, ensuring that any future manual ban management can remain "persistent", reviewable, and fully auditable.

Please read the [Installation Instructions](Setup-Instructions.md) carefully and secure your deployment with the provided `.htaccess`.
> Have in mind, that you are installing Beta Software that could contain bugs or can change with next release.

> Critical backend operations (like UFW updates) are executed via root cron scripts; ensure the server running Fail2Ban-Report is fully secured.

---

## 📚 What It Does

Fail2Ban-Report parses your `fail2ban.log` and generates JSON-based reports viewable via a responsive web dashboard.  
It provides optional tools to:

- 📊 View **ban/unban events** and per-jail statistics
- 🌐 Switch between multiple servers in a single dashboard
- 🔐 Use authentication with **viewer** (read-only) and **admin** (block/unblock) roles
- 📂 Maintain **persistent blocklists** (per jail and per server) with metadata (`active`, `pending`, `source`)
  - no fire & forget
- ⚡ Apply or remove firewall rules (currently via **ufw**)
- 🚨 Get configureable warnings for unusual activity (DDoS, brute-force, scans)
- 🚨 Mark IPs with 🔴 repeat bans or 🟡 ban increases
- 🔍 Optional integrations: (_Free API-KEYS_)
  - [AbuseIPDB](https://www.abuseipdb.com/) for reputation lookups
  - [IP-Info.io](https://ipinfo.io/) for region/provider checks

> **Note:** Viewer accounts are read-only. Direct integration with other firewalls or native Fail2Ban jail commands is not yet implemented.  


---

## 🧱 Architecture Overview

**Backend (Shell scripts):**
- Parse Fail2Ban logs → generate daily JSON event files
- Maintain and update jail-specific blocklists (`*.blocklist.json`)
- Sync blocklists with `ufw`
- Provide HTTPS endpoint for multi-server synchronization

**Frontend (PHP Web Interface):**
- Event timeline with filtering and search
- Per-jail blocklist view
- Multi-server dropdown
- Bulk actions (ban/unban/report)
- Pending status for actions not yet applied
- Warning/critical indicators for activity spikes
- Authentication: viewer (read-only) / admin (ban/unban)

**Blocklists (JSON):**
- Stored per jail and per server
- Include metadata: jail, status, timestamps, source
- Modified only by authenticated admins

---

## 📦 Features

- 🔍 Searchable & filterable event reports  
- 📊 Aggregated statistics (today, yesterday, 7 days, 30 days)  
- 📂 Jail- and server-specific blocklists  
- 🔄 Firewall sync with UFW  
- 🔐 Authentication with role separation  
- ⚡ Lightweight: no database, no frameworks  
- 🛠️ Setup scripts for installation, permissions, and user management  
- 🧩 Modular structure 
- 🪵 Optional backend logging for ban/unban actions  

> 🧰 Works even on small setups (Raspberry Pi, etc.)

---


## 🆕 What's New in v0.5.0

- 🌐 **Multi-server support** with HTTPS sync backend  
- 🔐 **User authentication** with roles (Admin / Viewer)  
- ⚙️ **Reorganized backend**:  
  - `archive/` separated per server (fail2ban / blocklists)  
  - `/opt/Fail2Ban-Report/` cleaned and structured  
  - Centralized path handling, less hardcoding  
- 🌐 **Frontend updates**:  
  - Server selection dropdown  
  - Admin login + logout (session handling)  
- 🔒 **Security updates**:  
  - Bcrypt password storage  
  - UUID and optional IP checks  
  - Additional `.htaccess` IP whitelist
 
---

## ⚙️ Requirements

- Fail2Ban with logging enabled  
- UFW (for firewall integration)    
- PHP 7.4+ with JSON support  
- HTTPS-capable web server (Apache or Nginx)  
- `bash` - (https://en.wikipedia.org/wiki/Bash_(Unix_shell))
- `jq`   - (https://jqlang.org/)
- `awk`  - (https://en.wikipedia.org/wiki/AWK)
- `curl` - (https://curl.se/)
---


### ⚠️ Upgrade Notice

If you're upgrading from an existing installation 

> here will be added some new stuff

---


## 👥 Discussions

> If you want to join the conversation or have questions or ideas, visit the 💬 [Discussions page](https://github.com/SubleXBle/Fail2Ban-Report/discussions).

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
- ✅ **Handling of "Increase Ban" Events** : will now processed correct by backend and is also visible in frontend via markers (0.4.0)
  - Thanks to ***jbd7*** for reporting and debugging 👍.
- ⏳ **Copy to Clipboard** cannot copy the list when filtered by markers

---

## 👀 Outlook
- 📦 will have a deeper look into statistics and more Fail2Ban Integration with the next releases

---

## 🛣️ Roadmap or "Things I will have to do - but I do them later"

> I gave up the usual Roadmap - to have more freedom with development - Things like Multiserver was never on the Roadmap but allways in my mind.

- ⏳ Rework Blocklist Overlay
- ⏳ Rework Stylesheet
- ⏳ Rework Info Notices

> As I am using Fail2Ban-Report I think it has a lot of potential to become something nice for not just myself.

> Suggestions and Ideas still welcome at any time (see Discussions) - When you are using Fail2Ban-Report and you think "I would need to see .. " tell me, I am happy to see your Ideas!

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
