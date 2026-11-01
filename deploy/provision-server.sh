#!/usr/bin/env bash
set -euo pipefail
base=/srv/enroute-preview
php=/opt/remi/php83/root/usr/bin/php
test -x "$php"
test ! -e "$base/jewelry"
getent passwd enroute >/dev/null || useradd --system --home-dir "$base" --shell /sbin/nologin enroute
unzip -q "$base/7.1.4.zip" -d "$base"
mv "$base/wordpress" "$base/jewelry"
unzip -q "$base/woocommerce.zip" -d "$base/jewelry/wp-content/plugins"
cp -a "$base/theme" "$base/jewelry/wp-content/themes/enroute-study"
mkdir -p "$base/jewelry/wp-content/reference"
install -m 0644 "$base/reference/necklaces.json" "$base/jewelry/wp-content/reference/necklaces.json"
install -m 0644 "$base/setup/import.php" "$base/import.php"
install -m 0644 "$base/deploy/php-fpm.conf" "$base/php-fpm.conf"
install -m 0644 "$base/deploy/enroute-php-fpm.service" /etc/systemd/system/enroute-php-fpm.service
install -m 0644 "$base/deploy/enroute-mariadb.cnf" /etc/my.cnf.d/enroute-preview.cnf
systemctl enable --now mariadb
"$php" "$base/deploy/bootstrap-server.php"
"$php" "$base/deploy/finish-server.php"
"$php" "$base/deploy/enable-customer-accounts.php"
chown -R enroute:enroute "$base/jewelry"
install -m 0644 "$base/deploy/enroute-backend.conf" /etc/nginx/conf.d/enroute-backend.conf
install -m 0644 "$base/deploy/enroute-public-locations.conf" /etc/nginx/enroute-public-locations.conf
install -m 0644 "$base/deploy/enroute-8080.conf" /etc/nginx/conf.d/enroute-8080.conf
systemctl daemon-reload
systemctl enable --now enroute-php-fpm
nginx -t
systemctl reload nginx
install -m 0644 "$base/deploy/enroute-preview.cron" /etc/cron.d/enroute-preview
systemctl is-active mariadb enroute-php-fpm nginx
echo 'Provisioning complete on port 8080. Admin credentials are stored in /root/enroute-preview-access.json; the catalog opens without preview authentication.'
