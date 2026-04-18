#!/usr/bin/env bash
#
# GeoMaxima NTRIP Caster - One-Line Installer
# Usage: curl -fsSL https://raw.githubusercontent.com/peshovp/CR/main/install.sh | sudo bash
#
# Requirements: Ubuntu 24.04 (fresh install), root access
# License: GPL-3.0 - Based on CasterREP (GPL-3.0)
#

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

BANNER='
   ____            __  __            _
  / ___| ___  ___ |  \/  | __ ___  _(_)_ __ ___   __ _
 | |  _ / _ \/ _ \| |\/| |/ _` \ \/ / | '"'"'_ ` _ \ / _` |
 | |_| |  __/ (_) | |  | | (_| |>  <| | | | | | | (_| |
  \____|\___|\___/|_|  |_|\__,_/_/\_\_|_| |_| |_|\__,_|
                NTRIP Caster v5.0.0
                    INSTALLER
'

echo -e "${BLUE}${BANNER}${NC}"

# =======================================
# STEP 0: Pre-flight checks
# =======================================
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}ERROR: Run this script as root (sudo)${NC}"
    exit 1
fi

if ! grep -q "Ubuntu 24" /etc/os-release 2>/dev/null; then
    echo -e "${YELLOW}WARNING: This script is designed for Ubuntu 24.04. Proceed? (y/n)${NC}"
    read -r confirm < /dev/tty
    [ "$confirm" != "y" ] && exit 1
fi

echo -e "${BLUE}=== Configuration ===${NC}"
echo ""

# MongoDB password
while true; do
    read -s -r -p "Set MongoDB password for geomaxima_admin: " MONGO_PASS < /dev/tty
    echo
    read -s -r -p "Confirm password: " MONGO_PASS2 < /dev/tty
    echo
    if [ "$MONGO_PASS" = "$MONGO_PASS2" ] && [ -n "$MONGO_PASS" ]; then
        break
    fi
    echo -e "${RED}Passwords don't match or are empty. Try again.${NC}"
done

# Web panel admin password
while true; do
    read -s -r -p "Set web panel admin password: " ADMIN_PASS < /dev/tty
    echo
    read -s -r -p "Confirm password: " ADMIN_PASS2 < /dev/tty
    echo
    if [ "$ADMIN_PASS" = "$ADMIN_PASS2" ] && [ -n "$ADMIN_PASS" ]; then
        break
    fi
    echo -e "${RED}Passwords don't match or are empty. Try again.${NC}"
done

VM_IP=$(hostname -I | awk '{print $1}')
echo ""
echo -e "${GREEN}VM IP detected: ${VM_IP}${NC}"
echo -e "${GREEN}MongoDB password: [set]${NC}"
echo -e "${GREEN}Admin password: [set]${NC}"
echo ""
read -r -p "Continue with installation? (y/n): " proceed < /dev/tty
[ "$proceed" != "y" ] && exit 0

INSTALL_DIR="/opt/geomaxima"
REPO_URL="https://github.com/peshovp/CR.git"
DB_NAME="geomaxima"
DB_USER="geomaxima_admin"

# =======================================
# STEP 1: System Update
# =======================================
echo -e "${BLUE}[1/9] Updating system...${NC}"
apt update && apt upgrade -y
apt install -y \
    git curl wget nano htop ufw \
    software-properties-common \
    gnupg

# =======================================
# STEP 2: Install MongoDB 7.0
# =======================================
echo -e "${BLUE}[2/9] Installing MongoDB 7.0...${NC}"

curl -fsSL https://www.mongodb.org/static/pgp/server-7.0.asc | \
    gpg -o /usr/share/keyrings/mongodb-server-7.0.gpg --dearmor --yes

echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-7.0.gpg ] https://repo.mongodb.org/apt/ubuntu jammy/mongodb-org/7.0 multiverse" | \
    tee /etc/apt/sources.list.d/mongodb-org-7.0.list

apt update
apt install -y mongodb-org

systemctl start mongod
systemctl enable mongod

sleep 3

mongosh --quiet <<MONGOEOF
use ${DB_NAME}
db.createUser({
    user: "${DB_USER}",
    pwd: "${MONGO_PASS}",
    roles: [{ role: "readWrite", db: "${DB_NAME}" }]
})
MONGOEOF

if ! grep -q "^security:" /etc/mongod.conf; then
    printf '\nsecurity:\n  authorization: enabled\n' >> /etc/mongod.conf
fi

sed -i 's/bindIp:.*/bindIp: 127.0.0.1/' /etc/mongod.conf

systemctl restart mongod
sleep 2

mongosh -u "$DB_USER" -p "$MONGO_PASS" --authenticationDatabase "$DB_NAME" \
    --quiet --eval "db.runCommand({ping:1})" "$DB_NAME"
echo -e "${GREEN}MongoDB installed and configured.${NC}"

# =======================================
# STEP 3: Install Python + Dependencies
# =======================================
echo -e "${BLUE}[3/9] Installing Python dependencies...${NC}"

apt install -y python3-pip python3-venv python3-dev

mkdir -p "$INSTALL_DIR"
cd "$INSTALL_DIR"

if [ -d ".git" ]; then
    git pull
else
    git clone "$REPO_URL" .
fi

python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt

echo -e "${GREEN}Python environment ready.${NC}"

# =======================================
# STEP 4: Install PHP 8.3 + Apache
# =======================================
echo -e "${BLUE}[4/9] Installing PHP 8.3 + Apache...${NC}"

apt install -y \
    apache2 \
    php8.3 php8.3-cli php8.3-common \
    php8.3-mbstring php8.3-xml php8.3-curl \
    libapache2-mod-php8.3 \
    php-dev php-pear

pecl install mongodb <<< ''
echo "extension=mongodb.so" > /etc/php/8.3/mods-available/mongodb.ini
phpenmod mongodb

php -m | grep -q mongodb \
    && echo -e "${GREEN}PHP MongoDB extension OK${NC}" \
    || echo -e "${RED}PHP MongoDB extension FAILED${NC}"

cd "$INSTALL_DIR/web"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
composer install --no-dev --no-interaction

echo -e "${GREEN}PHP + Apache installed.${NC}"

# =======================================
# STEP 5: Configure GeoMaxima
# =======================================
echo -e "${BLUE}[5/9] Configuring GeoMaxima...${NC}"

cd "$INSTALL_DIR"

cat > .env <<ENVEOF
MONGODB_HOST=127.0.0.1
MONGODB_PORT=27017
MONGODB_USER=${DB_USER}
MONGODB_PASSWORD=${MONGO_PASS}
MONGODB_DBNAME=${DB_NAME}
CASTER_HOST=0.0.0.0
CASTER_PORT=2101
CAPTURE_HOST=0.0.0.0
CAPTURE_PORT=2103
ENVEOF

chmod 600 .env

cp config_file.ini.example config_file.ini
sed -i "s|RTCM_CAPTURE_SERVER_HOST = .*|RTCM_CAPTURE_SERVER_HOST = '0.0.0.0'|" config_file.ini
sed -i "s|CASTER_SERVER_HOST = .*|CASTER_SERVER_HOST = '0.0.0.0'|" config_file.ini
sed -i "s|STR_MONGODB_AUTH_USER = .*|STR_MONGODB_AUTH_USER = '${DB_USER}'|" config_file.ini
sed -i "s|STR_MONGODB_AUTH_PASSWD = .*|STR_MONGODB_AUTH_PASSWD = '${MONGO_PASS}'|" config_file.ini
sed -i "s|str_db_Name = .*|str_db_Name = '${DB_NAME}'|" config_file.ini

chmod 600 config_file.ini

cat > "$INSTALL_DIR/web/conf.php" <<PHPEOF
<?php
\$ip = '127.0.0.1';
\$port = '27017';
\$user = '${DB_USER}';
\$pasw = '${MONGO_PASS}';
\$dashboard_rate = 15;
?>
PHPEOF

chmod 640 "$INSTALL_DIR/web/conf.php"

source venv/bin/activate

export MONGODB_HOST="127.0.0.1"
export MONGODB_PORT="27017"
export MONGODB_USER="${DB_USER}"
export MONGODB_PASSWORD="${MONGO_PASS}"
export MONGODB_DBNAME="${DB_NAME}"
export GEOMAXIMA_ADMIN_PASS="${ADMIN_PASS}"

python3 -c "
import bcrypt, os, sys, time
sys.path.insert(0, '.')
from general_defs import getDatabase

db = getDatabase()

password = os.environ['GEOMAXIMA_ADMIN_PASS']
hashed = bcrypt.hashpw(password.encode('utf-8'), bcrypt.gensalt()).decode('utf-8')

db['users'].delete_many({'username': 'admin'})
db['users'].insert_one({
    'username': 'admin',
    'password_hash': hashed,
    'first_name': 'Admin',
    'last_name': 'GeoMaxima',
    'organisation': 'GeoMaxima',
    'email': '',
    'phone': '',
    'city': '',
    'country': 'BGR',
    'zip_code': '',
    'description': 'System Administrator',
    'type': 0,
    'active': True,
    'valid_from': time.time()
})

db['streams'].delete_many({'mountpoint': 'NEAREST'})
db['streams'].insert_one({
    'mountpoint': 'NEAREST',
    'identifier': 'NEAREST',
    'data_format': 'RTCM 3.x',
    'format_detail': '',
    'carrier': 2,
    'nav_system': 'GPS+GLONASS+Galileo',
    'network': 'GeoMaxima',
    'country': 'BGR',
    'latitude': 42.6812,
    'longitude': 26.3292,
    'nmea': 1,
    'solution': False,
    'generator': 'GeoMaxima NTRIP Caster',
    'compr_encryp': 'none',
    'authentication': 'B',
    'fee': 'N',
    'bitrate': 9600,
    'misc': '',
    'encoder_pwd': '',
    'id_station': 0,
    'active': True
})

print('Database initialized: admin user + NEAREST mountpoint created.')
"

echo -e "${GREEN}Configuration complete.${NC}"

# =======================================
# STEP 6: Configure Apache
# =======================================
echo -e "${BLUE}[6/9] Configuring Apache...${NC}"

cat > /etc/apache2/sites-available/geomaxima.conf <<APACHEEOF
<VirtualHost *:80>
    ServerName geomaxima.local
    DocumentRoot ${INSTALL_DIR}/web

    <Directory ${INSTALL_DIR}/web>
        AllowOverride All
        Require ip 192.168.254.0/24
        Require ip 127.0.0.1
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/geomaxima_error.log
    CustomLog \${APACHE_LOG_DIR}/geomaxima_access.log combined
</VirtualHost>
APACHEEOF

a2ensite geomaxima.conf
a2dissite 000-default.conf
a2enmod rewrite

chown -R www-data:www-data "$INSTALL_DIR/web"
chmod -R 755 "$INSTALL_DIR/web"
chmod 640 "$INSTALL_DIR/web/conf.php"
chown root:www-data "$INSTALL_DIR/web/conf.php"

systemctl restart apache2
systemctl enable apache2

echo -e "${GREEN}Apache configured.${NC}"

# =======================================
# STEP 7: Create systemd Services
# =======================================
echo -e "${BLUE}[7/9] Creating systemd services...${NC}"

cat > /etc/systemd/system/geomaxima-caster.service <<SVCEOF
[Unit]
Description=GeoMaxima NTRIP Caster Server
After=network.target mongod.service
Requires=mongod.service

[Service]
Type=simple
User=root
WorkingDirectory=${INSTALL_DIR}
EnvironmentFile=${INSTALL_DIR}/.env
ExecStart=${INSTALL_DIR}/venv/bin/python3 ${INSTALL_DIR}/geomaxima_caster_server.py
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
SVCEOF

cat > /etc/systemd/system/geomaxima-capture.service <<SVCEOF
[Unit]
Description=GeoMaxima RTCM3 Capture Server
After=network.target mongod.service
Requires=mongod.service

[Service]
Type=simple
User=root
WorkingDirectory=${INSTALL_DIR}
EnvironmentFile=${INSTALL_DIR}/.env
ExecStart=${INSTALL_DIR}/venv/bin/python3 ${INSTALL_DIR}/geomaxima_rtcm3_capture.py
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
SVCEOF

systemctl daemon-reload
systemctl enable geomaxima-caster geomaxima-capture
systemctl start geomaxima-caster geomaxima-capture

sleep 3
echo -e "${GREEN}Services created and started.${NC}"

# =======================================
# STEP 8: Firewall
# =======================================
echo -e "${BLUE}[8/9] Configuring firewall...${NC}"

ufw --force reset
ufw default deny incoming
ufw default allow outgoing

ufw allow from 192.168.254.0/24 to any port 22 comment "SSH local"
ufw allow from 192.168.254.0/24 to any port 80 comment "Web panel local"
ufw allow from 192.168.254.0/24 to any port 2101 comment "NTRIP Caster"
ufw allow from 192.168.254.0/24 to any port 2103 comment "RTCM Capture"

ufw --force enable

echo -e "${GREEN}Firewall configured.${NC}"

# =======================================
# STEP 9: Verification
# =======================================
echo -e "${BLUE}[9/9] Running verification tests...${NC}"
echo ""

PASS=0
FAIL=0

check() {
    if eval "$2" > /dev/null 2>&1; then
        echo -e "  ${GREEN}OK${NC} $1"
        PASS=$((PASS + 1))
    else
        echo -e "  ${RED}FAIL${NC} $1"
        FAIL=$((FAIL + 1))
    fi
}

check "MongoDB running"         "systemctl is-active --quiet mongod"
check "Caster service running"  "systemctl is-active --quiet geomaxima-caster"
check "Capture service running" "systemctl is-active --quiet geomaxima-capture"
check "Apache running"          "systemctl is-active --quiet apache2"
check "Port 2101 listening"     "ss -tlnp | grep -q ':2101'"
check "Port 2103 listening"     "ss -tlnp | grep -q ':2103'"
check "Port 80 listening"       "ss -tlnp | grep -q ':80'"
check "Port 27017 listening"    "ss -tlnp | grep -q ':27017'"
check "Sourcetable response"    "curl -sf http://localhost:2101/ | grep -qi 'SOURCETABLE\|NEAREST'"
check "Web panel responds"      "curl -sf -o /dev/null -w '%{http_code}' http://localhost/ | grep -q '200\|302'"
check "PHP MongoDB extension"   "php -m | grep -q mongodb"
check "UFW active"              "ufw status | grep -q 'Status: active'"

echo ""
echo -e "${BLUE}=======================================${NC}"
echo -e "  Results: ${GREEN}${PASS} passed${NC}, ${RED}${FAIL} failed${NC}"
echo -e "${BLUE}=======================================${NC}"
echo ""

if [ "$FAIL" -eq 0 ]; then
    echo -e "${GREEN}Installation complete!${NC}"
    echo ""
    echo -e "  Web Panel:    ${BLUE}http://${VM_IP}/${NC}"
    echo -e "  Login:        admin / [your password]"
    echo ""
    echo -e "  Caster:       ${BLUE}${VM_IP}:2101${NC}"
    echo -e "  Capture:      ${BLUE}${VM_IP}:2103${NC}"
    echo ""
    echo -e "  Logs:         sudo journalctl -u geomaxima-caster -f"
    echo -e "                sudo journalctl -u geomaxima-capture -f"
    echo ""
    echo -e "${YELLOW}NEXT STEPS:${NC}"
    echo -e "  1. Open ${BLUE}http://${VM_IP}/${NC} and log in"
    echo -e "  2. Create a stream (mountpoint) for your base station"
    echo -e "  3. Configure base station -> ${VM_IP}:2103"
    echo -e "  4. Configure rover -> ${VM_IP}:2101"
    echo ""
else
    echo -e "${RED}Some checks failed. Review the output above and check:${NC}"
    echo "  sudo journalctl -u geomaxima-caster --no-pager -n 30"
    echo "  sudo journalctl -u geomaxima-capture --no-pager -n 30"
    echo "  sudo tail -20 /var/log/apache2/geomaxima_error.log"
fi
