# 🏫 Oldenburg Sprachklassen – Server Setup & Deployment

Dieses Repository dokumentiert die vollständige Installation, Einrichtung und das Deployment des Webservers  
**xxx.xxx.schule** für das Projekt **„Oldenburg Sprachklassen“**.

---

## ⚙️ Systemübersicht

| Komponente | Version / Technologie |
|-------------|-----------------------|
| **Betriebssystem** | Debian 12.x (Bookworm) |
| **Webserver** | Apache 2.4.x |
| **Scripting** | PHP 8.2 |
| **Datenbank** | MariaDB 10.11.x |
| **Reverse Proxy** | Nginx Proxy Manager (NPM) |
| **SSL/TLS** | Let’s Encrypt über NPM |
| **Firewall & Schutz** | UFW + Fail2Ban |
| **Hostname** | `xxx.xxx.schule` |
| **Interne IP** | `192.168.xxx.207` |
| **Reverse Proxy** | `xxx-xxx.xxx.de` (192.168.xxx.253) |

---

## ✅ Ziel

Nach einer Neuinstallation soll der Server mit wenigen Schritten wieder lauffähig sein:

- Apache + PHP + Extensions
- MariaDB
- Repo deployen (Read-only)
- **Composer install (vendor ist NICHT im Repo!)**
- `php bin/init_db.php` (legt DB, Tabellen, User/Grants an)
- Rechte für `uploads/` & `logs/`

---

## 🧩 1️⃣ Grundinstallation

```bash
su -
apt update && apt upgrade -y
apt install sudo vim curl wget unzip ufw net-tools ca-certificates -y
usermod -aG sudo user
timedatectl set-timezone Europe/Berlin
hostnamectl set-hostname xxx.xxx.schule
```

`/etc/hosts` anpassen:

```text
127.0.0.1    xxx.xxx.schule oldenburg localhost
```

---

## 🌐 2️⃣ Apache + PHP

```bash
sudo apt install apache2 libapache2-mod-php \
php php-cli php-common \
php-mysql php-xml php-curl php-zip php-mbstring php-intl php-gd php-fileinfo -y

sudo a2enmod php8.2 rewrite headers env dir mime
sudo systemctl enable apache2
sudo systemctl restart apache2
```

Testseite (optional):

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

---

## 🗄️ 3️⃣ MariaDB

```bash
sudo apt install mariadb-server -y
sudo mysql_secure_installation
```

### ✅ Empfohlen: dedizierter DB-Admin-User für Setup (statt root)

**Warum?** Auf Debian ist `root` häufig über `unix_socket` konfiguriert und nicht zuverlässig per Passwort/TCP nutzbar.  
Damit `bin/init_db.php` beim Neuaufsetzen immer funktioniert, ist ein Setup-Admin-User die stabilste Lösung.

Einmalig lokal auf dem Server ausführen:

```bash
sudo mariadb
```

SQL:

```sql
CREATE USER 'db_admin'@'localhost' IDENTIFIED BY 'STRONGPASS';
GRANT ALL PRIVILEGES ON *.* TO 'db_admin'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

> Hinweis: `bin/init_db.php` hat zusätzlich einen Fallback auf `host=localhost` (Socket), falls `DB_ADMIN_DSN` über TCP nicht funktioniert. Trotzdem ist `db_admin` für Neuinstallationen am robustesten.

---

## 🌍 4️⃣ Apache VirtualHost

`/etc/apache2/sites-available/000-xxx.xxx.schule.conf`:

```apache
<VirtualHost *:80>
    ServerName xxx.xxx.schule
    ServerAlias www.xxx.xxx.schule
    DocumentRoot /var/www/xxx.xxx.schule/public

    <Directory /var/www/xxx.xxx.schule/public>
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
sudo a2ensite 000-xxx.xxx.schule.conf
sudo systemctl reload apache2
```

> Falls euer Projekt nicht `public/` als DocumentRoot nutzt, bitte oben wieder auf `/var/www/xxx.xxx.schule` ändern.

---

## 🔁 5️⃣ Reverse Proxy (Nginx Proxy Manager)

**Proxy Host:**

| Feld | Wert |
|------|------|
| Domain Names | `xxx.xxx.schule` |
| Scheme | `http` |
| Forward Hostname / IP | `192.168.xxx.207` |
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
sudo ufw allow from 192.168.xxx.253 to any port 80 proto tcp
sudo ufw enable

sudo apt install fail2ban unattended-upgrades -y
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 🧰 7️⃣ PHP-Optimierung (Uploads)

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 16M/' /etc/php/*/apache2/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 32M/' /etc/php/*/apache2/php.ini
sudo systemctl reload apache2
```

Hinweis (falls PHP-FPM genutzt wird):  
Bitte zusätzlich `/etc/php/x.x/fpm/php.ini` prüfen und dort analog anpassen.

---

## 🔄 8️⃣ Repository Deployment (Read-Only GitHub Zugriff)

### 📦 Vorbereitung

```bash
sudo apt install git -y
sudo install -d -o user -g www-data -m 2775 /var/www/xxx.xxx.schule
```

### 🔐 Deploy Key

Auf dem Server:

```bash
ssh-keygen -t ed25519 -C "deploy@xxx.xxx.schule"
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

```text
Hi KITS2015! You've successfully authenticated, but GitHub does not provide shell access.
```

### 📥 Repository klonen

```bash
sudo -u user git clone git@github.com:KITS2015/Oldenburg_Sprachklassen.git /var/www/xxx.xxx.schule
```

---

## 📦 9️⃣ Composer (Pflicht: vendor ist NICHT im Repo!)

### Composer installieren

```bash
sudo apt install composer -y
composer --version
```

### Dependencies installieren

```bash
cd /var/www/xxx.xxx.schule
sudo -u user composer install --no-dev --optimize-autoloader
```

### ⚠️ Wichtiger Hinweis zu composer.json / composer.lock (Fix für euren aktuellen Zustand)

Im Code wird Composer-Autoload zwingend benötigt:

- `app/email.php` lädt `vendor/autoload.php` (PHPMailer)
- `public/application_pdf.php` lädt `vendor/autoload.php` und nutzt `TCPDF` (**tecnickcom/tcpdf**)

➡️ Daher muss `composer.json` im Repo alle tatsächlich genutzten Pakete enthalten und `composer.lock` muss dazu passen.

**Aktueller Fix (im Repo durchführen, NICHT auf dem Server “händisch”):**
1. Auf einem Dev-System im Repo:
   ```bash
   composer require tecnickcom/tcpdf
   ```
2. Prüfen, dass danach `composer.json` und `composer.lock` aktualisiert sind.
3. Beides committen & pushen.
4. Auf dem Server deployen → `composer install` läuft und installiert korrekt.

> Optional (wenn Bootstrap NICHT via Composer gewollt ist): `composer remove twbs/bootstrap`  
> (Frage dazu siehe unten.)

---

## 🗄️ 1️⃣0️⃣ Datenbank initialisieren (Schema + User/Grants)

### 🔧 Konfiguration

DB-Zugangsdaten liegen in `app/config.php`.

Wichtige Konstanten:
- `APP_DB_NAME` (z.B. `oldenburg_app`)
- `APP_DB_USER` (z.B. `oldenburg_user`)
- `APP_DB_PASS`
- `DB_ADMIN_*` nur für `bin/init_db.php`

### ✅ Init ausführen

```bash
cd /var/www/xxx.xxx.schule
php bin/init_db.php
```

Erwartete Ausgabe:

```text
[OK] Datenbank ist aktuell (Tabellen/Spalten/Indizes ergänzt, nichts gelöscht).
```

### Was macht `init_db.php` genau?
- legt die Datenbank `APP_DB_NAME` an (falls nicht vorhanden)
- legt DB-User `APP_DB_USER` an
- setzt/aktualisiert Rechte (GRANTs) für:
  - `localhost`, `127.0.0.1`, `::1`
- erstellt/erweitert Tabellen, Spalten, Indizes, Foreign Keys (idempotent)
- legt Default-Setting `max_tokens_per_email` an

---

## 🧱 1️⃣1️⃣ Dateirechte (Server)

```bash
sudo chown -R user:www-data /var/www/xxx.xxx.schule
sudo find /var/www/xxx.xxx.schule -type d -exec chmod 2755 {} \;
sudo find /var/www/xxx.xxx.schule -type f -exec chmod 0644 {} \;
```

### Schreibbare Verzeichnisse

#### uploads/

```bash
sudo install -d -o user -g www-data -m 2775 /var/www/xxx.xxx.schule/uploads
sudo find /var/www/xxx.xxx.schule/uploads -type d -exec chmod 2775 {} \;
sudo find /var/www/xxx.xxx.schule/uploads -type f -exec chmod 0664 {} \;
```

#### logs/ (falls genutzt)

```bash
sudo install -d -o user -g www-data -m 2775 /var/www/xxx.xxx.schule/logs
sudo find /var/www/xxx.xxx.schule/logs -type d -exec chmod 2775 {} \;
sudo find /var/www/xxx.xxx.schule/logs -type f -exec chmod 0664 {} \;
```

### Option: ACLs (empfohlen, wenn Rechte später “driften”)

```bash
sudo apt-get update
sudo apt-get install -y acl

sudo setfacl -R -m u:user:rwx,g:www-data:rwx /var/www/xxx.xxx.schule/uploads
sudo setfacl -d -m u:user:rwx,g:www-data:rwx /var/www/xxx.xxx.schule/uploads

getfacl /var/www/xxx.xxx.schule/uploads
```

---

## 🧪 1️⃣2️⃣ Smoke Tests (nach Neuinstallation)

### PHP + Extensions

```bash
php -v
php -m | egrep -i "pdo_mysql|mbstring|intl|curl|openssl|json|fileinfo"
```

### Composer Autoload vorhanden?

```bash
test -f /var/www/xxx.xxx.schule/vendor/autoload.php && echo "autoload OK" || echo "autoload MISSING"
```

### DB-Connect (App-User) + Tabellen-Check

```bash
cd /var/www/xxx.xxx.schule
php -r "require 'app/config.php'; \$dsn='mysql:host=127.0.0.1;dbname='.APP_DB_NAME.';port=3306;charset=utf8mb4'; \$pdo=new PDO(\$dsn, APP_DB_USER, APP_DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]); echo 'DB OK: '.\$pdo->query('SELECT DATABASE()')->fetchColumn().PHP_EOL; echo 'Tables: '.count(\$pdo->query('SHOW TABLES')->fetchAll()).PHP_EOL;"
```

### Web-Erreichbarkeit lokal prüfen

```bash
curl -I http://127.0.0.1/ | head
```

---

## 🚀 1️⃣3️⃣ Deployment: GitHub → Server (deploy.sh)

Im Repo liegt ein **One-way** `deploy.sh` (GitHub → Server).  
Es verwirft lokale Änderungen auf dem Server (tracked + untracked), behält aber definierte Runtime-Pfade.

### Runtime-Pfade, die NICHT gelöscht/überschrieben werden sollen
- `uploads/`
- `logs/`
- `.env`
- `app/config.php`
- (weitere siehe `deploy.sh` EXCLUDES)

### ⚠️ Wichtig: vendor wird beim Deploy gelöscht, danach MUSS Composer laufen
Da `vendor/` nicht im Repo ist, muss `deploy.sh` nach dem Git-Reset ein `composer install` ausführen.

**Empfohlener Ablauf:**
```bash
cd /var/www/xxx.xxx.schule
sudo -u user ./deploy.sh deploy
sudo -u user composer install --no-dev --optimize-autoloader
sudo systemctl reload apache2
```

> Optional: Wenn ihr Composer direkt in `deploy.sh` integrieren wollt:  
> Ich empfehle, `deploy.sh` um einen Composer-Schritt zu erweitern (dann ist ein Deploy “vollständig” in einem Lauf).

---

## 🕓 1️⃣4️⃣ Automatische Updates (Cron)

Wenn ihr nightly Pulls wollt, ist das sauberste:

```bash
sudo crontab -e
```

Einfügen (03:00 Uhr):

```text
0 3 * * * cd /var/www/xxx.xxx.schule && sudo -u user ./deploy.sh deploy && sudo -u user composer install --no-dev --optimize-autoloader && systemctl reload apache2 >/dev/null 2>&1
```

---

## ✅ 🔚 Zusammenfassung

| Komponente | Pfad / Funktion |
|-------------|-----------------|
| Apache vHost | `/etc/apache2/sites-available/000-xxx.xxx.schule.conf` |
| Webroot | `/var/www/xxx.xxx.schule` (DocumentRoot meist `/public`) |
| PHP-Version | 8.2 |
| MariaDB | 10.11.x |
| DB Init | `php bin/init_db.php` |
| Composer | `composer install --no-dev --optimize-autoloader` (**Pflicht**) |
| Reverse Proxy | Nginx Proxy Manager |
| SSL | Let’s Encrypt |
| Firewall | UFW + Fail2Ban |
| HTTPS-Erkennung | `X-Forwarded-Proto` → Apache Rewrite setzt `HTTPS=on` |
| Deploy | `./deploy.sh deploy` (GitHub → Server, one-way) |

---

## 🪶 Autoren & Credits

**Projekt:** Oldenburg Sprachklassen  
**Betreuung & Infrastruktur:** Kuhlmann IT Solutions (KITS)  
**Version:** 1.3 – Stand Januar 2026
