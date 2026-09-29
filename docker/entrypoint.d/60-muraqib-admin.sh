#!/bin/sh
# Create the first admin from ADMIN_EMAIL / ADMIN_PASSWORD on first start
# (there's no public sign-up). Safe to run on every start: an existing admin is left alone.
# Runs with the other startup automations (migrations, caches), so only in the web container.
if [ "${AUTORUN_ENABLED:-false}" = "true" ] && [ "${MURAQIB_SEED_ADMIN:-true}" = "true" ]; then
    echo "Muraqib: making sure the first admin exists..."
    php /var/www/html/artisan db:seed --force --no-interaction
fi
