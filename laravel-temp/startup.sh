#!/bin/bash

echo "Clearing cache..."
php artisan optimize:clear 2>&1 || echo "Cache clear failed, skipping..."

echo "Running migrations..."
php artisan migrate --force --no-interaction 2>&1 || echo "Migration failed, skipping..."

echo "Running seeders..."
php artisan db:seed --force --no-interaction 2>&1 || echo "Seed failed, skipping..."

echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
