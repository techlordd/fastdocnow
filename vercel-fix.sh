#!/bin/bash

# Vercel Deployment Fix Script
# This script fixes common issues with Laravel on Vercel

echo "🔧 Fixing Laravel for Vercel deployment..."

# Create necessary directories
mkdir -p /tmp/storage/framework/{sessions,views,cache}
mkdir -p /tmp/storage/logs
mkdir -p /tmp/bootstrap/cache

# Set permissions
chmod -R 755 /tmp/storage
chmod -R 755 /tmp/bootstrap/cache

# Create .env if it doesn't exist
if [ ! -f .env ]; then
    cp .env.example .env
fi

# Generate app key if not set
if ! grep -q "^APP_KEY=" .env || grep -q "^APP_KEY=$" .env; then
    php artisan key:generate
fi

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Vercel deployment fixes applied!"
