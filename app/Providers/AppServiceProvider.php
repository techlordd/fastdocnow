<?php

namespace App\Providers;

use App\Auth\WordPressUserProvider;
use App\Services\WordPressAuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register WordPress Auth Service
        $this->app->singleton(WordPressAuthService::class, function ($app) {
            return new WordPressAuthService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register WordPress User Provider
        Auth::provider('wordpress', function ($app, array $config) {
            return new WordPressUserProvider($config['model']);
        });

        // Configure Corcel if WordPress is enabled
        if (config('wordpress.enabled', false)) {
            $this->configureCorcel();
        }
    }

    /**
     * Configure Corcel WordPress integration.
     */
    protected function configureCorcel(): void
    {
        // Set Corcel configuration from our config files
        if (class_exists('\Corcel\Laravel\CorcelServiceProvider')) {
            // Ensure Corcel uses our WordPress database connection
            config([
                'corcel.connection' => config('wordpress.connection', 'wordpress'),
                'corcel.prefix' => config('wordpress.prefix', 'wp_'),
            ]);
        }
    }
}
