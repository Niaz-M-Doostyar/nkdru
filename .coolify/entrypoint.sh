#!/bin/sh
set -eu
mkdir -p \
  /var/www/html/cache/t_cache \
  /var/www/html/cache/t_compile \
  /var/www/html/cache/_db \
  /var/www/html/files \
  /var/www/html/public
chown -R ojs:ojs /var/www/html/cache
chmod -R ug+rwX /var/www/html/cache
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n
