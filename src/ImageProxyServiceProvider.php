<?php

namespace TryHard\ImageProxy;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use TryHard\ImageProxy\Http\Controllers\ImageProxyController;

class ImageProxyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/image-proxy.php', 'image-proxy');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/image-proxy.php' => config_path('image-proxy.php'),
        ], 'image-proxy-config');

        if (config('image-proxy.route.enabled', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $config = config('image-proxy.route', []);

        Route::middleware($config['middleware'] ?? ['web'])
            ->group(function () use ($config) {
                $prefix = $config['prefix'] ?? null;
                $name = $config['name'] ?? 'image-proxy.show';

                $uri = $prefix
                    ? "{$prefix}/{options}/{path}"
                    : "{options}/{path}";

                Route::get($uri, [ImageProxyController::class, 'show'])
                    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
                    ->where('path', '.*\.[a-zA-Z0-9]+')
                    ->name($name);
            });
    }
}
