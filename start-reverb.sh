#!/bin/bash

# Start Reverb server for local development
echo "🚀 Starting Reverb WebSocket server..."

# Check if .env exists
if [ ! -f .env ]; then
    echo "❌ .env file not found. Please create one from .env.example"
    exit 1
fi

# Load environment variables
source .env

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed or not in PATH"
    exit 1
fi

# Check if Laravel is installed
if [ ! -f artisan ]; then
    echo "❌ Laravel artisan file not found. Please run 'composer install' first"
    exit 1
fi

# Start Reverb
echo "✅ Starting Reverb on port ${REVERB_PORT:-8080}..."
php artisan reverb:start --host=0.0.0.0 --port=${REVERB_PORT:-8080}
