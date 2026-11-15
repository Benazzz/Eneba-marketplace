#!/bin/sh
# Container start script (Render's free tier has no shell, so setup runs on every boot).
# Every step is idempotent: running it again on the next boot changes nothing.
set -e  # stop on the first failing command, so a broken DB connection fails the deploy loudly

# Apply pending database migrations. --allow-no-migration: don't fail while there are none yet.
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Fill an empty database with demo data. --if-empty: skip (and succeed) when products already exist.
php bin/console app:seed --if-empty --no-interaction

# The console ran as root and may have written to var/; give it back to Apache's user.
chown -R www-data:www-data var

# Replace this shell with Apache (PID 1), so it receives Render's stop signals directly.
exec apache2-foreground
