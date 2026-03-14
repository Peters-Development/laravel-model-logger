<?php

namespace PetersDevelopment\ModelLogger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PetersDevelopment\ModelLogger\ModelLoggerServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ModelLoggerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
