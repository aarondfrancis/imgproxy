<?php

namespace AaronFrancis\ImgProxy\Tests;

use AaronFrancis\ImgProxy\ImgProxyServiceProvider;
use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageServiceProvider::class,
            ImgProxyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('imgproxy.rate_limit.enabled', false);
        $app['config']->set('imgproxy.route.middleware', []);
        $app['config']->set('imgproxy.sources', [
            'images' => [
                'disk' => 'public',
            ],
        ]);
    }
}
