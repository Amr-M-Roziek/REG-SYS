#!/bin/bash
# VPS Setup Script for regsys.cloud

# Stop on error
set -e

echo ">>> Starting VPS Setup..."

# 1. Install Dependencies (LAMP Stack)
echo ">>> Installing Dependencies..."
apt-get update -q
DEBIAN_FRONTEND=noninteractive apt-get install -y apache2 mariadb-server php libapache2-mod-php php-mysql php-curl php-gd php-mbstring php-xml php-zip unzip

# 2. Configure Firewall
echo ">>> Configuring Firewall..."
ufw allow OpenSSH
ufw allow 'Apache Full'
# Avoid locking out if already enabled
ufw --force enable

# 3. Setup Database
echo ">>> Setting up Database..."
DB_PASS="regsys@2025"

# Secure Installation (Automated)
mysql -e "DELETE FROM mysql.user WHERE User='';"
mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
mysql -e "DROP DATABASE IF EXISTS test;"
mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"

# Create Users
# We create all potential users found in the codebase and give them the same password for simplicity
mysql -e "CREATE USER IF NOT EXISTS 'regsys_reg'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "CREATE USER IF NOT EXISTS 'regsys_poster'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "CREATE USER IF NOT EXISTS 'regsys_part'@'localhost' IDENTIFIED BY '$DB_PASS';"

# 4. Import Data & Create DBs Dynamically
echo ">>> Importing Data..."
# Enable globbing for .sql files
shopt -s nullglob

for sql_file in *.sql; do
    # Extract filename without extension (e.g., "regsys_reg.sql" -> "regsys_reg")
    DB_NAME="${sql_file%.*}"
    
    echo " - Processing $sql_file -> Database: $DB_NAME"
    
    # Create DB
    mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME;"
    
    # Grant Permissions (Grant ALL to all our system users for this DB to ensure no access denied issues)
    mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO 'regsys_reg'@'localhost';"
    mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO 'regsys_poster'@'localhost';"
    mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO 'regsys_part'@'localhost';"
    
    # Import
    echo "   Importing SQL..."
    mysql "$DB_NAME" < "$sql_file"
done

mysql -e "FLUSH PRIVILEGES;"

# 5. Setup Web Files
echo ">>> Setting up Web Files..."
WEB_ROOT="/var/www/html/icpm2026"
mkdir -p "$WEB_ROOT"

if [ -f "project.zip" ]; then
    echo " - Unzipping project..."
    unzip -o -q project.zip -d "$WEB_ROOT"
    
    # Fix Permissions
    chown -R www-data:www-data "$WEB_ROOT"
    chmod -R 755 "$WEB_ROOT"
    
    # Ensure temp/uploads folders are writable
    mkdir -p "$WEB_ROOT/temp" "$WEB_ROOT/uploads"
    chmod -R 777 "$WEB_ROOT/temp" "$WEB_ROOT/uploads"
    
    echo " - Files deployed to $WEB_ROOT"
fi

# 6. Configure Apache
echo ">>> Configuring Apache..."
a2enmod rewrite

# Enable .htaccess overrides
CONF_FILE="/etc/apache2/sites-available/000-default.conf"
if ! grep -q "AllowOverride All" "$CONF_FILE"; then
    sed -i '/DocumentRoot \/var\/www\/html/a \    <Directory /var/www/html>\n        AllowOverride All\n    </Directory>' "$CONF_FILE"
fi

systemctl restart apache2

echo ">>> Setup Complete! Access at http://69.62.119.120/icpm2026/"
