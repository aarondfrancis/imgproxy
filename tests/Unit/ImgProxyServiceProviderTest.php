<?php

use AaronFrancis\ImgProxy\ImgProxyService;
use AaronFrancis\ImgProxy\ImgProxyServiceProvider;
use Illuminate\Support\Facades\Route;

it('merges config on register', function () {
    // Config should be merged from the package config file
    expect(config('imgproxy'))->toBeArray();
    expect(config('imgproxy.route'))->toBeArray();
    expect(config('imgproxy.route.enabled'))->toBeBool();
    expect(config('imgproxy.default_quality'))->toBe(85);
    expect(config('imgproxy.max_width'))->toBe(2000);
    expect(config('imgproxy.max_height'))->toBe(2000);
    expect(config('imgproxy.allowed_formats'))->toBe(['jpg', 'jpeg', 'png', 'gif', 'webp']);
});

it('binds ImgProxyService as singleton', function () {
    $instance1 = app(ImgProxyService::class);
    $instance2 = app(ImgProxyService::class);

    expect($instance1)->toBeInstanceOf(ImgProxyService::class);
    expect($instance1)->toBe($instance2);
});

it('registers routes when enabled', function () {
    // Routes are enabled by default in TestCase
    $route = Route::getRoutes()->getByName('imgproxy.show');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('{options}/{source}/{path}');
    expect($route->methods())->toContain('GET');
});

it('does not register routes when disabled', function () {
    // Override config
    $this->app['config']->set('imgproxy.route.enabled', false);

    // Clear routes and re-register the provider
    $this->app['router']->setRoutes(new \Illuminate\Routing\RouteCollection);

    $provider = new ImgProxyServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    $routes = $this->app['router']->getRoutes();
    $routes->refreshNameLookups();

    $route = $routes->getByName('imgproxy.show');

    expect($route)->toBeNull();
});

it('applies route prefix correctly', function () {
    // Set the route config with prefix
    $this->app['config']->set('imgproxy.route', [
        'enabled' => true,
        'prefix' => 'img',
        'middleware' => [],
        'name' => 'imgproxy.show',
    ]);

    // Clear routes and re-register the provider
    $this->app['router']->setRoutes(new \Illuminate\Routing\RouteCollection);

    $provider = new ImgProxyServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    // Need to refresh name lookups after adding routes
    $routes = $this->app['router']->getRoutes();
    $routes->refreshNameLookups();

    $route = $routes->getByName('imgproxy.show');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('img/{options}/{source}/{path}');
});

it('registers route without prefix when prefix is null', function () {
    // The default TestCase sets prefix to null via mergeConfigFrom
    $route = Route::getRoutes()->getByName('imgproxy.show');

    expect($route)->not->toBeNull();
    expect($route->uri())->toBe('{options}/{source}/{path}');
    expect($route->uri())->not->toStartWith('/');
});

it('applies custom route name', function () {
    // Set the route config with custom name
    $this->app['config']->set('imgproxy.route', [
        'enabled' => true,
        'prefix' => null,
        'middleware' => [],
        'name' => 'custom.image.route',
    ]);

    // Clear routes and re-register the provider
    $this->app['router']->setRoutes(new \Illuminate\Routing\RouteCollection);

    $provider = new ImgProxyServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    // Need to refresh name lookups after adding routes
    $routes = $this->app['router']->getRoutes();
    $routes->refreshNameLookups();

    $customRoute = $routes->getByName('custom.image.route');
    $defaultRoute = $routes->getByName('imgproxy.show');

    expect($customRoute)->not->toBeNull();
    expect($defaultRoute)->toBeNull();
});

it('applies middleware to routes', function () {
    // Set the route config with middleware
    $this->app['config']->set('imgproxy.route', [
        'enabled' => true,
        'prefix' => null,
        'middleware' => ['web', 'auth'],
        'name' => 'imgproxy.show',
    ]);

    // Clear routes and re-register the provider
    $this->app['router']->setRoutes(new \Illuminate\Routing\RouteCollection);

    $provider = new ImgProxyServiceProvider($this->app);
    $provider->register();
    $provider->boot();

    // Need to refresh name lookups after adding routes
    $routes = $this->app['router']->getRoutes();
    $routes->refreshNameLookups();

    $route = $routes->getByName('imgproxy.show');

    expect($route)->not->toBeNull();
    expect($route->middleware())->toContain('web');
    expect($route->middleware())->toContain('auth');
});

it('constrains source parameter to configured sources', function () {
    $route = Route::getRoutes()->getByName('imgproxy.show');

    // The route should have whereIn constraint for sources
    $wheres = $route->wheres;

    // The 'source' constraint is set via whereIn which creates a regex pattern
    expect($wheres)->toHaveKey('source');
    // Should match 'images' or 'media' from TestCase config
    expect($wheres['source'])->toContain('images');
    expect($wheres['source'])->toContain('media');
});

it('constrains options parameter format', function () {
    $route = Route::getRoutes()->getByName('imgproxy.show');
    $wheres = $route->wheres;

    expect($wheres)->toHaveKey('options');
    // Should match key=value format
    expect($wheres['options'])->toBe('([a-zA-Z]+=[a-zA-Z0-9]+,?)+');
});

it('constrains path parameter to include file extension', function () {
    $route = Route::getRoutes()->getByName('imgproxy.show');
    $wheres = $route->wheres;

    expect($wheres)->toHaveKey('path');
    // Should match paths ending with file extension
    expect($wheres['path'])->toBe('.+\.[a-zA-Z0-9]+');
});

it('publishes config file', function () {
    // Get the paths to publish for the 'imgproxy-config' tag
    $paths = ImgProxyServiceProvider::pathsToPublish(ImgProxyServiceProvider::class, 'imgproxy-config');

    expect($paths)->not->toBeEmpty();

    $sourcePath = array_key_first($paths);
    $destinationPath = $paths[$sourcePath];

    expect($sourcePath)->toEndWith('config/imgproxy.php');
    expect($destinationPath)->toBe(config_path('imgproxy.php'));
});
