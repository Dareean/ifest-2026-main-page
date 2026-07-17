#!/bin/bash
set -e

echo "Setting cache directory permissions..."
chmod -R 777 bootstrap/cache/ 2>/dev/null || true

echo "Removing old cache files..."
rm -f bootstrap/cache/config.php bootstrap/cache/routes-v7.php bootstrap/cache/packages.php bootstrap/cache/services.php 2>/dev/null || true

echo "Caching config..."
php artisan config:cache 2>&1 || echo "Config cache skipped"

echo "Caching routes..."
php artisan route:cache 2>&1 || echo "Route cache skipped"

echo "Running migrations..."
php artisan migrate --force --no-interaction --seed 2>&1

echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
