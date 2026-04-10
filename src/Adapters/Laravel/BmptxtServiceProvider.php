<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\Laravel;

use Illuminate\Support\ServiceProvider;
use TsengYuChen\BMPtxt\BmpConverter;
use TsengYuChen\BMPtxt\Core\Font\FontManager;

/**
 * Laravel Service Provider for BMPtxt.
 *
 * Auto-discovered via composer.json extra.laravel.providers.
 *
 * Publishes:
 *   php artisan vendor:publish --tag=bmptxt-config  → config/bmptxt.php
 *   php artisan vendor:publish --tag=bmptxt-fonts   → resources/fonts/bmptxt/
 */
class BmptxtServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge package config with any user-published config
        $this->mergeConfigFrom(
            __DIR__ . '/config/bmptxt.php',
            'bmptxt'
        );

        // Bind BmpConverter as a singleton in the service container
        $this->app->singleton(BmpConverter::class, function ($app): BmpConverter {
            $fontsPath = $app['config']->get('bmptxt.fonts_path');
            $converter = BmpConverter::create($fontsPath ?: null);

            if ($app->bound('cache.store')) {
                // cache.store returns Illuminate\Contracts\Cache\Repository
                // which implements Psr\SimpleCache\CacheInterface
                
                $ttl = $app['config']->get('bmptxt.cache_ttl', 3600);
                if ($ttl !== 0 && $ttl !== false) {
                    $converter->withCache($app['cache.store'], $ttl);
                }
            }

            return $converter;
        });

        // Register short alias so Facade & service('bmptxt') both work
        $this->app->alias(BmpConverter::class, 'bmptxt');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__ . '/config/bmptxt.php' => config_path('bmptxt.php'),
            ], 'bmptxt-config');

            // Publish bundled fonts so users can customise them
            $this->publishes([
                dirname(__DIR__, 3) . '/fonts' => resource_path('fonts/bmptxt'),
            ], 'bmptxt-fonts');
        }
    }

    /**
     * @return array<string>
     */
    public function provides(): array
    {
        return [BmpConverter::class, 'bmptxt'];
    }
}
