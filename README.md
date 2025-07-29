# Fail2Ban-Report

A simple and clean web-based reporting tool for Fail2Ban events.  
Turn your daily Fail2Ban logs into searchable and filterable JSON reports – right on your webspace.

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



## 📝 Release-Notes
+ Stylesheet have been moved to style.css for easy customization.
+ The JSON output is plain and lightweight. You can post-process or archive old data easily.
+ This tool requires no database and can run even on very minimal webspace setups. (e.g. RaspberryPi)

## Protecting Your Fail2Ban Report with .htaccess

To enhance the security of your Fail2Ban report, a `.htaccess` file is provided that:

- Disables directory listings
- Blocks direct access to sensitive files such as `.json` and `.css`
- Sets basic HTTP security headers for safer browsing

### How to Use the `.htaccess` File

1. Save the provided `.htaccess` file in the root directory of your Fail2Ban report (where `index.php` resides).
2. Ensure your web server allows `.htaccess` overrides (typically via `AllowOverride` in Apache).
3. The `.htaccess` will automatically protect files in the main directory and subfolders like `/archive/`.

### Important Security Notice

While this `.htaccess` provides a basic level of protection, **it is highly recommended to implement additional security measures**, such as:

- HTTP authentication (Basic Auth) to restrict access to authorized users only
- IP-based access restrictions to allow only trusted networks or addresses

Fail2Ban reports often contain sensitive security-related data. Adding these layers of protection will help prevent unauthorized access and keep your data safe.

For example, you can set up Basic Auth with:

```apache
AuthType Basic
AuthName "Restricted Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

You can use the htpasswd helper for your htpasswd files (choose bcrypt as algorythm as it is better) on [https://suble.net/htpasswd/](https://suble.net/htpasswd/) (⚠️ german language)

or restrict by IP:

```
Require ip 192.168.1.0/24
Require ip 203.0.113.5
```

## 📄 License
This project is released under the GPLv3 License. Feel free to modify and share.

---

## 🗺️ Roadmap

**Fail2Ban-Report** is designed to be lightweight, modular, and open to future improvements. The following features are currently planned:

### ⚙️ Setup & Automation
- Setup script to automate initial installation, including directory structure and permissions
- Optionally auto-configure a daily cronjob

### 🔐 Security Features
- Integration of a stronger `.htaccess` file for basic access control and secure defaults

### 🔥 Active Defense Integration
- Allow manual IP blocking directly from the interface via `iptables` or `ufw`
- Optionally enable automatic blocking of suspicious IPs based on defined criteria

## 🧩 Open to Contributions
I'm happy to hear from users and contributors!  
Whether it's:
- feature requests,  
- improvement ideas,  
- or even pull requests —  
Feel free to reach out or contribute directly.

If you use this tool and think "Hey, wouldn't it be cool if it could also do XYZ?" — I'm all ears!
