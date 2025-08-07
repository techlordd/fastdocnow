#!/bin/bash

echo "🔧 Fixing DocNow Chat Local Setup..."

# Clear all caches
echo "📋 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Clear compiled views
echo "🗑️ Clearing compiled views..."
php artisan view:clear

# Regenerate autoloader
echo "📦 Regenerating autoloader..."
composer dump-autoload

# Run database migrations if needed
echo "🗄️ Running migrations..."
php artisan migrate --force

# Create storage link if not exists
echo "🔗 Creating storage link..."
php artisan storage:link

# Install dependencies if needed
echo "📥 Installing dependencies..."
npm install

echo "✅ Local setup fixed! You can now:"
echo "1. Start Reverb server: php artisan reverb:start"
echo "2. Start queue worker: php artisan queue:work" 
echo "3. Start dev server: php artisan serve"
echo "4. Start frontend: npm run dev"
echo ""
echo "🌐 Your app should be available at: https://pc-connection.test/"
