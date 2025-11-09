# 🏫 Oldenburg Sprachklassen – Server Setup & Deployment

Dieses Repository dokumentiert die vollständige Installation, Einrichtung und das Deployment des Webservers  
**oldenburg.anmeldung.schule** für das Projekt **„Oldenburg Sprachklassen“**.

---

## ⚙️ Systemübersicht

| Komponente | Version / Technologie |
|-------------|-----------------------|
| **Betriebssystem** | Debian 12.x (Bookworm) |
| **Webserver** | Apache 2.4.65 |
| **Scripting** | PHP 8.2 |
| **Datenbank** | MariaDB 10.11.x |
| **Reverse Proxy** | Nginx Proxy Manager (NPM) |
| **SSL/TLS** | Let’s Encrypt über NPM |
| **Firewall & Schutz** | UFW + Fail2Ban |
| **Hostname** | `oldenburg.anmeldung.schule` |
| **Interne IP** | `192.168.84.207` |
| **Reverse Proxy** | `kits-reverseproxy.kuhlmann-its.de` (192.168.84.253) |

---

## 🧩 1️⃣ Grundinstallation

```bash
su -
apt update && apt upgrade -y
apt install sudo vim curl wget unzip ufw net-tools -y
usermod -aG sudo user
timedatectl set-timezone Europe/Berlin
hostnamectl set-hostname oldenburg.anmeldung.schule
```

`/etc/hosts` anpassen:
```
127.0.0.1    oldenburg.anmeldung.schule oldenburg localhost
```

---

## 🌐 2️⃣ Apache + PHP

```bash
sudo apt install apache2 libapache2-mod-php php php-cli php-common php-mysql \
php-xml php-curl php-zip php-mbstring php-intl php-gd -y
sudo a2enmod php8.2 rewrite headers env dir mime
sudo systemctl enable apache2
sudo systemctl restart apache2
```

Testseite:
```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

---

## 🗄️ 3️⃣ MariaDB

```bash
sudo apt install mariadb-server -y
sudo mysql_secure_installation
```

SQL:
```sql
CREATE DATABASE anmeldung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'anmeldung'@'localhost' IDENTIFIED BY 'GeheimesPasswort';
GRANT ALL PRIVILEGES ON anmeldung.* TO 'anmeldung'@'localhost';
FLUSH PRIVILEGES;
```

---

## 🌍 4️⃣ Apache VirtualHost

`/etc/apache2/sites-available/000-oldenburg.anmeldung.schule.conf`:

```apache
<VirtualHost *:80>
    ServerName oldenburg.anmeldung.schule
    ServerAlias www.oldenburg.anmeldung.schule
    DocumentRoot /var/www/oldenburg.anmeldung.schule

    <Directory /var/www/oldenburg.anmeldung.schule>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/sprachklassen_error.log
    CustomLog ${APACHE_LOG_DIR}/sprachklassen_access.log "%v %A H=\"%{Host}i\" XFF=\"%{X-Forwarded-For}i\" \"%r\" %>s %b"

    RewriteEngine On
    RewriteCond %{HTTP:X-Forwarded-Proto} ^https(?:,|$)
    RewriteRule ^ - [E=HTTPS:on]

    <IfModule mod_headers.c>
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-Content-Type-Options "nosniff"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </IfModule>
</VirtualHost>
```

Aktivieren:
```bash
sudo a2ensite 000-oldenburg.anmeldung.schule.conf
sudo systemctl reload apache2
```

---

## 🔁 5️⃣ Reverse Proxy (Nginx Proxy Manager)

**Proxy Host:**
| Feld | Wert |
|------|------|
| Domain Names | `oldenburg.anmeldung.schule` |
| Scheme | `http` |
| Forward Hostname / IP | `192.168.84.207` |
| Forward Port | `80` |
| Access List | Publicly Accessible |
| SSL | Let’s Encrypt aktiv, „Force SSL“ aktiviert |

**Custom location `/`:**
```nginx
proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
proxy_set_header X-Forwarded-Proto https;
```
**Advanced:** leer lassen (kein `proxy_set_header Host`!)

---

## 🔒 6️⃣ Firewall & Sicherheit

```bash
sudo ufw default deny incoming
sudo ufw allow OpenSSH
sudo ufw allow from 192.168.84.253 to any port 80 proto tcp
sudo ufw enable
sudo apt install fail2ban unattended-upgrades -y
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 🧰 7️⃣ PHP-Optimierung

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 16M/' /etc/php/*/apache2/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 32M/' /etc/php/*/apache2/php.ini
sudo systemctl reload apache2
```

---

## 🔄 8️⃣ Repository Deployment (Read-Only GitHub Zugriff)

### 📦 Vorbereitung

```bash
sudo apt install git ca-certificates -y
sudo install -d -o user -g www-data -m 2775 /var/www/oldenburg.anmeldung.schule
```

### 🔐 Deploy Key (empfohlen)

Auf dem Server:
```bash
ssh-keygen -t ed25519 -C "deploy@oldenburg.anmeldung.schule"
cat ~/.ssh/id_ed25519.pub
```

→ Schlüssel in GitHub unter  
**Settings → Deploy keys → Add deploy key**  
**Allow write access deaktivieren!** ✅

Test:
```bash
sudo -u user ssh -T git@github.com
```

Erwartete Ausgabe:
```
Hi KITS2015! You've successfully authenticated, but GitHub does not provide shell access.
```

### 📥 Repository klonen

```bash
sudo -u user git clone git@github.com:KITS2015/Oldenburg_Sprachklassen.git /var/www/oldenburg.anmeldung.schule
```

### 🧱 Rechte

```bash
sudo chown -R user:www-data /var/www/oldenburg.anmeldung.schule
sudo find /var/www/oldenburg.anmeldung.schule -type d -exec chmod 2755 {} \;
sudo find /var/www/oldenburg.anmeldung.schule -type f -exec chmod 0644 {} \;
```

### 🧭 Update-Skript (Read-only Pull)

`/usr/local/bin/update-sprachklassen.sh`:

```bash
#!/bin/bash
set -e
cd /var/www/oldenburg.anmeldung.schule
sudo -u user git fetch --all
sudo -u user git reset --hard origin/main
sudo systemctl reload apache2
```

```bash
sudo chmod +x /usr/local/bin/update-sprachklassen.sh
```

### 🕓 Cronjob (täglich um 03:00 Uhr)

```bash
sudo crontab -e
# Einfügen:
0 3 * * * /usr/local/bin/update-sprachklassen.sh >/dev/null 2>&1
```

Test:
```bash
sudo /usr/local/bin/update-sprachklassen.sh
```

Erwartung: Repository wird aktualisiert, Apache neu geladen.

---

## 🧪 9️⃣ Testseite

`/var/www/oldenburg.anmeldung.schule/index.php`:

```php
<?php
echo "Host: " . $_SERVER['HTTP_HOST'] . "<br>";
echo "Client-IP (REMOTE_ADDR): " . $_SERVER['REMOTE_ADDR'] . "<br>";
echo "X-Forwarded-For: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '-') . "<br>";
echo "X-Forwarded-Proto: " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '-') . "<br>";
echo "HTTPS erkannt? " . (($_SERVER['HTTPS'] ?? getenv('HTTPS')) === 'on' ? 'ja' : 'nein') . "<br>";
echo "Zeit: " . date('Y-m-d H:i:s');
?>
```

Erwartete Ausgabe:
```
Host: oldenburg.anmeldung.schule
Client-IP (REMOTE_ADDR): <deine-IP>
X-Forwarded-For: <deine-IP>
X-Forwarded-Proto: https
HTTPS erkannt? ja
Zeit: <Datum/Uhrzeit>
```

---

## ✅ 🔚 Zusammenfassung

| Komponente | Pfad / Funktion |
|-------------|-----------------|
| Apache vHost | `/etc/apache2/sites-available/000-oldenburg.anmeldung.schule.conf` |
| Webroot | `/var/www/oldenburg.anmeldung.schule` |
| PHP-Version | 8.2 |
| Datenbank | `anmeldung` (MariaDB) |
| Reverse Proxy | Nginx Proxy Manager |
| SSL | Let’s Encrypt |
| Firewall | UFW + Fail2Ban |
| HTTPS-Erkennung | `X-Forwarded-Proto` |
| Zugriff | Nur über Proxy (192.168.84.253) |
| Repo Update | Automatisch per `git fetch --all` (read-only) |

---

## 🚀 Deployment-Skript (`deploy.sh`)

Das Skript automatisiert die Synchronisation zwischen **Server und GitHub**  
und liegt unter `/var/www/oldenburg.anmeldung.schule/deploy.sh`.

### 🔧 Funktionsweise

- Erkennung lokaler Änderungen → automatischer Commit (`git add -A && git commit`)
- Prüfung auf Änderungen in GitHub → automatischer Pull (`git pull --ff-only` oder `--rebase`)
- Push lokaler Änderungen zu GitHub (`git push origin main`)
- Vollständiges Logging unter  
  `/var/www/oldenburg.anmeldung.schule/logs/git_deploy_oldenburg.log`

---

### 🧭 Befehlsübersicht

| Befehl | Richtung | Beschreibung |
|:--------|:----------|:--------------|
| `./deploy.sh` oder `./deploy.sh sync` | 🔄 **Beide Richtungen** | Synchronisiert Server ↔ GitHub automatisch. Erzeugt Commits bei lokalen Änderungen und pusht oder pullt je nach Status. |
| `./deploy.sh push "Kommentar"` | ⬆️ **Server → GitHub** | Committet lokale Änderungen (inkl. neuer Dateien) und pusht sie zum Remote. |
| `./deploy.sh pull` | ⬇️ **GitHub → Server** | Holt Änderungen aus GitHub und führt ggf. Rebase aus. |
| `./deploy.sh status` | 📋 **Statusabfrage** | Zeigt Remote-URLs, Branch-Status und letzte Commits. |
| `./deploy.sh help` | ❔ **Hilfe** | Zeigt Kurzbeschreibung aller Befehle. |

---

### 🪶 Beispielabläufe

**Neue Datei auf dem Server anlegen**
```bash
cd /var/www/oldenburg.anmeldung.schule/public
nano kontakt.php
/var/www/oldenburg.anmeldung.schule/deploy.sh


---

## 🪶 Autoren & Credits
**Projekt:** Oldenburg Sprachklassen  
**Betreuung & Infrastruktur:** Kuhlmann IT Solutions (KITS)  
**Version:** 1.1 – Stand November 2025

