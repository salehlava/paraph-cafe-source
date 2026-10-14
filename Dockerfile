# Paraph Café — full website (menu, customer club, admin panel) on PHP 8.3 + Apache.
#
#   docker compose up -d --build        → http://localhost:9000
#
# Railway / other hosts: deploy this Dockerfile and add a volume at /var/www/html/storage
# (the SQLite database lives there — without a volume, menu edits are lost on every deploy).
FROM php:8.3-apache

# PHP: production settings (errors are logged, never shown to visitors; no version header)
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php = Off\n' > "$PHP_INI_DIR/conf.d/zz-paraph.ini"

# Apache: obey the project's .htaccess rules (they block storage/, lib/, includes/, data/…),
# send security headers, hide the version, pass TRUST_PROXY to PHP
RUN a2enmod headers rewrite \
 && sed -ri '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf \
 && printf 'ServerTokens Prod\nServerSignature Off\nPassEnv TRUST_PROXY\n' > /etc/apache2/conf-available/paraph.conf \
 && a2enconf paraph

COPY docker/entrypoint.sh /usr/local/bin/paraph-entrypoint
COPY . /var/www/html/
RUN sed -i 's/\r$//' /usr/local/bin/paraph-entrypoint \
 && chmod +x /usr/local/bin/paraph-entrypoint \
 && rm -rf /var/www/html/docker /var/www/html/Dockerfile /var/www/html/docker-compose.yml \
 && mkdir -p /var/www/html/storage \
 && chown -R www-data:www-data /var/www/html/storage

# The site sits behind the host's proxy (Railway etc.): read the real visitor IP / HTTPS from it.
# docker-compose sets this to 0 because there the site is reached directly.
ENV TRUST_PROXY=1

VOLUME /var/www/html/storage
EXPOSE 80
CMD ["paraph-entrypoint"]
