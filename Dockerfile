FROM easyvate/php-ojs:8.2-migration
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx supervisor curl \
    && groupadd --gid 1000 ojs \
    && useradd --uid 1000 --gid 1000 --no-create-home --shell /usr/sbin/nologin ojs \
    && sed -i 's/^user = .*/user = ojs/; s/^group = .*/group = ojs/' /usr/local/etc/php-fpm.d/www.conf \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . /var/www/html
COPY .coolify/nginx.conf /etc/nginx/sites-enabled/default
COPY .coolify/supervisord.conf /etc/supervisor/conf.d/ojs.conf
COPY .coolify/entrypoint.sh /usr/local/bin/ojs-entrypoint
RUN chmod 0755 /usr/local/bin/ojs-entrypoint \
    && rm -f /var/www/html/config.inc.php \
    && mkdir -p /var/www/html/cache /var/www/html/files /var/www/html/public \
    && chown -R ojs:ojs /var/www/html/cache
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=10 \
  CMD curl -fsS http://127.0.0.1:8080/favicon.ico >/dev/null || exit 1
ENTRYPOINT ["/usr/local/bin/ojs-entrypoint"]
