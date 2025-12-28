<?php

namespace TryHard\ImageProxy\Tests;

use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use TryHard\ImageProxy\ImageProxyServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageServiceProvider::class,
            ImageProxyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('image-proxy.rate_limit.enabled', false);
        $app['config']->set('image-proxy.route.middleware', []);
    }
}
