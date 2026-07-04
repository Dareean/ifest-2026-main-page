#!/bin/bash

echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration failed, skipping..."

echo "Caching config..."
php artisan config:cache 2>&1 || echo "Config cache failed, skipping..."

echo "Caching routes..."
php artisan route:cache 2>&1 || echo "Route cache failed, skipping..."

echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
