#!/usr/bin/env bash
set -euo pipefail
base=/srv/seleneith-preview
php=/opt/remi/php83/root/usr/bin/php
test -x "$php"
test ! -e "$base/jewelry"
getent passwd seleneith >/dev/null || useradd --system --home-dir "$base" --shell /sbin/nologin seleneith
unzip -q "$base/7.1.4.zip" -d "$base"
mv "$base/wordpress" "$base/jewelry"
unzip -q "$base/woocommerce.zip" -d "$base/jewelry/wp-content/plugins"
cp -a "$base/theme" "$base/jewelry/wp-content/themes/seleneith"
mkdir -p "$base/jewelry/wp-content/reference"
install -m 0644 "$base/reference/necklaces.json" "$base/jewelry/wp-content/reference/necklaces.json"
install -m 0644 "$base/setup/import.php" "$base/import.php"
install -m 0644 "$base/deploy/php-fpm.conf" "$base/php-fpm.conf"
install -m 0644 "$base/deploy/seleneith-php-fpm.service" /etc/systemd/system/seleneith-php-fpm.service
install -m 0644 "$base/deploy/seleneith-mariadb.cnf" /etc/my.cnf.d/seleneith-preview.cnf
systemctl enable --now mariadb
"$php" "$base/deploy/bootstrap-server.php"
"$php" "$base/deploy/finish-server.php"
"$php" "$base/deploy/enable-customer-accounts.php"
chown -R seleneith:seleneith "$base/jewelry"
install -m 0644 "$base/deploy/seleneith-backend.conf" /etc/nginx/conf.d/seleneith-backend.conf
install -m 0644 "$base/deploy/seleneith-public-locations.conf" /etc/nginx/seleneith-public-locations.conf
install -m 0644 "$base/deploy/seleneith-8080.conf" /etc/nginx/conf.d/seleneith-8080.conf
systemctl daemon-reload
systemctl enable --now seleneith-php-fpm
nginx -t
systemctl reload nginx
install -m 0644 "$base/deploy/seleneith-preview.cron" /etc/cron.d/seleneith-preview
systemctl is-active mariadb seleneith-php-fpm nginx
echo 'Provisioning complete on port 8080. Admin credentials are stored in /root/seleneith-preview-access.json; the catalog opens without preview authentication.'
