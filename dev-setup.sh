#!/bin/bash

# Development Setup Script
# This script sets up the Laravel application for local development

echo "🚀 Setting up FastDocNow for development..."

# Make scripts executable
chmod +x start-reverb.sh
chmod +x vercel-fix.sh

# Install dependencies
echo "📦 Installing PHP dependencies..."
composer install

echo "📦 Installing Node.js dependencies..."
npm install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    
    # Generate app key
    php artisan key:generate
fi

# Create database if it doesn't exist
if [ ! -f database/database.sqlite ]; then
    echo "🗄️ Creating SQLite database..."
    touch database/database.sqlite
fi

# Run migrations and seeders
echo "🔄 Running migrations and seeders..."
php artisan migrate --seed

# Build assets
echo "🔨 Building assets..."
npm run build

# Create storage link
echo "🔗 Creating storage link..."
php artisan storage:link

echo "✅ Development setup complete!"
echo ""
echo "🎯 To start development:"
echo "1. Run 'npm run dev' for Vite"
echo "2. Run './start-reverb.sh' for WebSocket server"
echo "3. Run 'npm run queue' for queue worker"
echo "4. Run 'php artisan serve' for Laravel server"
echo ""
echo "🌐 Access the app at: http://localhost:8000"
