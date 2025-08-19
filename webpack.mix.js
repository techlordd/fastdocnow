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

// Main application assets
mix.js('resources/js/app.js', 'public/js')
    .js('resources/js/chat.js', 'public/js')
    .js('resources/js/voice-recorder.js', 'public/js')
    .js('resources/js/emoji-picker-mart.js', 'public/js')
    .postCss('resources/css/app.css', 'public/css', [
        require('tailwindcss'),
        require('autoprefixer'),
    ])
    .postCss('resources/css/emoji-picker.css', 'public/css', [
        require('autoprefixer'),
    ]);

// Third-party assets
mix.styles('node_modules/select2/dist/css/select2.min.css', 'public/css/select2.min.css');
mix.js('node_modules/select2/dist/js/select2.min.js', 'public/js/select2.min.js');

// Only copy directories if they exist
if (fs.existsSync('resources/images')) {
    mix.copyDirectory('resources/images', 'public/images');
}

if (fs.existsSync('resources/fonts')) {
    mix.copyDirectory('resources/fonts', 'public/fonts');
}

if (fs.existsSync('resources/sounds')) {
    mix.copyDirectory('resources/sounds', 'public/sounds');
}

// Enhanced webpack configuration for Pusher and real-time features
mix.webpackConfig({
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
        },
        fallback: {
            "fs": false,
            "path": false,
            "crypto": false
        }
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            }
        ]
    }
});

// Enable source maps for development
mix.sourceMaps(false, 'source-map');

// Optimize for production
if (mix.inProduction()) {
    mix.version();
    mix.options({
        terser: {
            terserOptions: {
                compress: {
                    drop_console: true,
                    drop_debugger: true
                }
            }
        }
    });
} else {
    // Development optimizations
    mix.options({
        hmrOptions: {
            host: 'localhost',
            port: 8080
        }
    });
}

// Hot module replacement for development
if (process.env.npm_lifecycle_event === 'hot') {
    mix.options({
        hmrOptions: {
            host: 'localhost',
            port: 8080,
        }
    });
}
