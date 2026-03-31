#!/usr/bin/env bash
# fix-upload-limits.sh
#
# Run this script once on the server after "git pull" to make large file
# uploads work.  It handles both PHP-FPM servers and classic mod_php servers.
#
# Usage (run as root or with sudo):
#   sudo bash /var/www/html/sbcqr/qr/bin/fix-upload-limits.sh
#
set -euo pipefail

APP_DIR="/var/www/html/sbcqr/qr"

echo "=== SBC QR Inventory — upload-limit fix script ==="
echo ""

# -----------------------------------------------------------------------
# 1. Ensure .user.ini exists in the app directory (PHP-FPM reads this).
#    The file ships with the repository, so "git pull" should have placed
#    it at $APP_DIR/.user.ini already.  This step is a safety net.
# -----------------------------------------------------------------------
if [ -f "$APP_DIR/.user.ini" ]; then
    echo "[OK] .user.ini found at $APP_DIR/.user.ini"
else
    echo "[INFO] .user.ini not found — creating it now..."
    cat > "$APP_DIR/.user.ini" <<'INI'
; PHP per-directory settings for PHP-FPM.
upload_max_filesize = 50M
post_max_size       = 55M
INI
    echo "[OK] .user.ini created"
fi

# -----------------------------------------------------------------------
# 2. Restart PHP-FPM so it picks up .user.ini immediately.
#    (Without a restart it can take up to 5 minutes.)
# -----------------------------------------------------------------------
echo ""
echo "--- Restarting PHP-FPM ---"
RESTARTED_FPM=0
for svc in php8.3-fpm php8.2-fpm php8.1-fpm php8.0-fpm php7.4-fpm php-fpm; do
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
        echo "Restarting $svc..."
        systemctl restart "$svc"
        echo "[OK] $svc restarted"
        RESTARTED_FPM=1
        break
    fi
done
if [ "$RESTARTED_FPM" -eq 0 ]; then
    echo "[INFO] No active PHP-FPM service found — your server may use mod_php (see below)"
fi

# -----------------------------------------------------------------------
# 3. If this server uses Apache mod_php, ensure AllowOverride All is set
#    for the /var/www/html/sbcqr/qr directory.
# -----------------------------------------------------------------------
echo ""
echo "--- Checking Apache configuration ---"
CONF_FILE=""
for f in /etc/apache2/sites-enabled/*.conf; do
    if grep -q "sbcqr" "$f" 2>/dev/null; then
        CONF_FILE="$f"
        break
    fi
done

if [ -z "$CONF_FILE" ]; then
    echo "[INFO] Could not locate the sbcqr Apache virtual host file."
    echo "       If uploads still fail, open the .conf file in /etc/apache2/sites-enabled/"
    echo "       and make sure the <Directory /var/www/html/sbcqr/qr> block says:"
    echo "           AllowOverride All"
else
    echo "Found Apache config: $CONF_FILE"
    if grep -q "AllowOverride All" "$CONF_FILE"; then
        echo "[OK] AllowOverride All is already set"
    else
        echo "[INFO] Patching $CONF_FILE — changing AllowOverride None → AllowOverride All ..."
        # Only patch the qr-specific Directory block
        sed -i '/Directory \/var\/www\/html\/sbcqr\/qr/{n;s/AllowOverride None/AllowOverride All/}' "$CONF_FILE"
        echo "[OK] Patched"
        echo "Reloading Apache..."
        systemctl reload apache2
        echo "[OK] Apache reloaded"
    fi
fi

# -----------------------------------------------------------------------
# 4. Print a summary.
# -----------------------------------------------------------------------
echo ""
echo "=== Done ==="
echo ""
echo "Try uploading a photo at https://sbcqr.com/qr/ now."
echo "Photos up to 10 MB and documents up to 50 MB should work."
echo ""
echo "If you still see 'File too large', run:"
echo "   php -r \"echo ini_get('upload_max_filesize');\""
echo "It should print '50M'.  If it prints '2M' or similar, check that"
echo "PHP-FPM restarted successfully (see 'systemctl status phpX.X-fpm')."
