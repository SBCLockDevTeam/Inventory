#!/bin/bash
# SBCInv Install Script
# Run this once on the server to create the database, user, and tables.
# You will be prompted for the MariaDB root password.

DB_NAME="SBCInv"
DB_USER="SBCInv"
SCHEMA="/var/www/html/sbcqr/qr/db/schema.sql"

echo "Enter the password you want to set for the $DB_USER database user:"
read -s DB_PASS

echo "Enter your MariaDB root password when prompted..."

mysql -u root -p <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
EOF

echo "Running schema..."
mysql -u root -p ${DB_NAME} < ${SCHEMA}

echo ""
echo "Done! Database ${DB_NAME} created with user ${DB_USER}."
echo "Now copy /var/www/html/sbcqr/qr/config/secrets.php.example to secrets.php and set your password.",