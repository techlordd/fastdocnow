#!/bin/bash

echo "🔧 Fixing Reverb configuration..."

# Clear configuration cache
php artisan config:clear

echo "🚀 Starting Reverb server..."

# Start Reverb with explicit configuration
php artisan reverb:start --host=127.0.0.1 --port=8080

echo "✅ Reverb should now be running on 127.0.0.1:8080"
