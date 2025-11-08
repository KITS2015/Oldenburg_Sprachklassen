# 🏩 Oldenburg Sprachklassen – Server Setup & Deployment

Dieses Repository dokumentiert die vollständige Installation und Einrichtung des Webservers  
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

## 🧩 1. Grundinstallation

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

## 🌐 2. Apache + PHP

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

## 💄 3. MariaDB

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

## 🌍 4. Apache VirtualHost

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

## 🔁 5. Reverse Proxy (Nginx Proxy Manager)

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

## 🔒 6. Firewall & Sicherheit

```bash
sudo ufw default deny incoming
sudo ufw allow OpenSSH
sudo ufw allow from 192.168.84.253 to any port 80 proto tcp
sudo ufw enable
sudo apt install fail2ban unattended-upgrades -y
sudo dpkg-reconfigure --priority=low unattended-upgrades
```

---

## 🧮 7. PHP-Optimierung

```bash
sudo sed -i 's/^upload_max_filesize.*/upload_max_filesize = 16M/' /etc/php/*/apache2/php.ini
sudo sed -i 's/^post_max_size.*/post_max_size = 32M/' /etc/php/*/apache2/php.ini
sudo systemctl reload apache2
```

---

## 🧪 8. Testseite

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

## ✅ 9. Zusammenfassung

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

---

## 🩶 Autoren & Credits
**Projekt:** Oldenburg Sprachklassen  
**Betreuung & Infrastruktur:** Kuhlmann IT Solutions (KITS)  
**Version:** 1.0 – Stand November 2025