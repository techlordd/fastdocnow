<?php

// Vercel entry point for Laravel
// This file serves as the main entry point for Vercel deployment

// Set the public path
$publicPath = __DIR__ . '/../public';

// Ensure we're using the correct document root
$_SERVER['DOCUMENT_ROOT'] = $publicPath;

// Set storage path for Vercel
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/storage/framework/views';
$_ENV['APP_STORAGE'] = '/tmp/storage';

// Create necessary directories
$dirs = [
    '/tmp/storage/framework/views',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/logs',
    '/tmp/bootstrap/cache'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Change to the public directory
chdir($publicPath);

// Include Laravel's public index.php
require_once $publicPath . '/index.php';
