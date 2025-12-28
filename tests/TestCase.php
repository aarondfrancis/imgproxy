<?php

namespace TryHard\ImageProxy\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use TryHard\ImageProxy\ImageProxyServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ImageProxyServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('image-proxy.rate_limit.enabled', false);
    }
}
