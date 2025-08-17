#!/bin/bash

# Laravel Storage Permissions Setup for Hostinger
# Run this script after uploading files to set proper permissions

echo "Setting up Laravel storage permissions for Hostinger..."

# Set directory permissions (755)
find storage -type d -exec chmod 755 {} \;
find bootstrap/cache -type d -exec chmod 755 {} \;

# Set file permissions (644)
find storage -type f -exec chmod 644 {} \;
find bootstrap/cache -type f -exec chmod 644 {} \;

# Ensure specific directories are writable
chmod -R 755 storage/app
chmod -R 755 storage/framework
chmod -R 755 storage/logs
chmod -R 755 bootstrap/cache

echo "Storage permissions set successfully!"

# Create necessary directories if they don't exist
mkdir -p storage/app/public/messages/images
mkdir -p storage/app/public/messages/audios
mkdir -p storage/app/public/messages/videos
mkdir -p storage/app/public/messages/files
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views

echo "Storage directories created successfully!"
echo "Please run 'php artisan storage:link' to create symbolic link for public storage."
