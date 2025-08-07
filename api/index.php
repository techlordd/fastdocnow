<?php

// Vercel entry point for Laravel
// This file serves as the main entry point for Vercel deployment

// Set the public path
$publicPath = __DIR__ . '/../public';

// Ensure we're using the correct document root
$_SERVER['DOCUMENT_ROOT'] = $publicPath;

// Change to the public directory
chdir($publicPath);

// Include Laravel's public index.php
require_once $publicPath . '/index.php';
