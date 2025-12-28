<?php

namespace AaronFrancis\ImgProxy;

use AaronFrancis\ImgProxy\Http\Controllers\ImgProxyController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ImgProxyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/imgproxy.php', 'imgproxy');

        $this->app->singleton(ImgProxyService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/imgproxy.php' => config_path('imgproxy.php'),
            ], 'imgproxy-config');
        }

        if (config('imgproxy.route.enabled', true)) {
            $this->registerRoutes();
        }
    }

    protected function registerRoutes(): void
    {
        $config = config('imgproxy.route', []);
        $sources = array_keys(config('imgproxy.sources', []));

        Route::middleware($config['middleware'] ?? [])
            ->group(function () use ($config, $sources) {
                $prefix = $config['prefix'] ?? null;
                $name = $config['name'] ?? 'imgproxy.show';

                $uri = $prefix
                    ? "{$prefix}/{options}/{source}/{path}"
                    : '{options}/{source}/{path}';

                Route::get($uri, [ImgProxyController::class, 'show'])
                    ->where('options', '([a-zA-Z]+=[a-zA-Z0-9]+,?)+')
                    ->whereIn('source', $sources)
                    ->where('path', '.+\.[a-zA-Z0-9]+')
                    ->name($name);
            });
    }
}
