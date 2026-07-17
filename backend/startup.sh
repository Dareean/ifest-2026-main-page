#!/bin/bash
set -e

echo "Caching config..."
php artisan config:cache 2>&1

echo "Caching routes..."
php artisan route:cache 2>&1 || echo "Route cache skipped"

echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1

echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
