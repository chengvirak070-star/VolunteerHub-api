#!/bin/sh
# Runs every time the Render service starts.
set -e

php artisan storage:link || true
php artisan config:cache
php artisan route:cache

# Create any new tables (never deletes data). Seeding is done once, by hand.
php artisan migrate --force

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
