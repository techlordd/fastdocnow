const mix = require('laravel-mix');
const fs = require('fs');
const path = require('path');

mix.autoload({
    jquery: ['$', 'window.jQuery']
});

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/chat.js', 'public/js')
    .js('resources/js/emoji-picker.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    .postCss('resources/css/emoji-picker.css', 'public/css');

mix.styles('node_modules/select2/dist/css/select2.min.css', 'public/css/select2.min.css');
mix.js('node_modules/select2/dist/js/select2.min.js', 'public/js/select2.min.js');

// Only copy directories if they exist
if (fs.existsSync('resources/images')) {
    mix.copyDirectory('resources/images', 'public/images');
}

if (fs.existsSync('resources/fonts')) {
    mix.copyDirectory('resources/fonts', 'public/fonts');
}

mix.sourceMaps();

if (mix.inProduction()) {
    mix.version();
}