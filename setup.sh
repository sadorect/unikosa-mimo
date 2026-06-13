#!/bin/bash

set -e

echo "=== UNIKOSA Alumni Platform Setup ==="

# Copy .env if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
    echo "✓ .env file created"
fi

# Build Docker containers
echo "Building Docker containers..."
docker compose build --no-cache

# Start services
echo "Starting services..."
docker compose up -d

# Wait for database
echo "Waiting for PostgreSQL..."
sleep 5

# Install PHP dependencies
echo "Installing PHP dependencies..."
docker compose exec app composer install

# Install NPM dependencies
echo "Installing NPM dependencies..."
docker compose exec app npm install

# Generate app key
echo "Generating app key..."
docker compose exec app php artisan key:generate --force

# Run migrations and seeders
echo "Running migrations..."
docker compose exec app php artisan migrate --force

echo "Running seeders..."
docker compose exec app php artisan db:seed --force

# Publish assets
echo "Publishing vendor assets..."
docker compose exec app php artisan vendor:publish --force --all 2>/dev/null || true

# Build frontend assets
echo "Building frontend assets..."
docker compose exec app npm run build

# Link storage
echo "Linking storage..."
docker compose exec app php artisan storage:link

echo ""
echo "=== Setup Complete ==="
echo ""
echo "App URL: http://localhost"
echo "Admin URL: http://localhost/admin"
echo ""
echo "Default credentials:"
echo "  Admin: admin@unikosa.org / password"
echo "  Member: john@example.com / password"
