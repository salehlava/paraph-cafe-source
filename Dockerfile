# Paraph Café on Railway: the whole PHP site (menu, customer club, admin with username + password).
#
# Railway: New Project → Deploy from GitHub repo → this repository.
#   Volume:    mount at /app/storage   (the database — without it, edits are lost on every deploy)
#   Variables: ADMIN_USERNAME, ADMIN_PASSWORD (min. 8 characters) — used once, to create the admin
#
# Pages are served through router.php, which only allows the public pages, /admin and /assets,
# so the database and code files can never be downloaded.
FROM php:8.3-cli

WORKDIR /app
COPY . /app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'expose_php = Off\n' > "$PHP_INI_DIR/conf.d/zz-paraph.ini" \
 && mkdir -p /app/storage

# Behind Railway's proxy: real visitor IP / HTTPS come from X-Forwarded-* headers.
# PHP_CLI_SERVER_WORKERS: handle several visitors at the same time.
ENV TRUST_PROXY=1 \
    PHP_CLI_SERVER_WORKERS=4 \
    PORT=8080

EXPOSE 8080

# Create the database (and the admin the first time), then start the site on Railway's port.
CMD php lib/init_admin.php && exec php -S 0.0.0.0:${PORT} router.php
