<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Tests;

use ArtisanBuild\Scalpels\Providers\ScalpelsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Base test case for Scalpels package tests.
 *
 * Extends Orchestra Testbench to provide Laravel testing environment.
 */
abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            ScalpelsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Set up environment configuration for tests
        $app['config']->set('scalpels.url', null);
        $app['config']->set('scalpels.project_id', null);
        $app['config']->set('scalpels.service_token', null);
        $app['config']->set('scalpels.webhook_secret', null);
        $app['config']->set('scalpels.default_account', 'default');
        $app['config']->set('scalpels.timeout', 30);
        $app['config']->set('scalpels.debug', false);
    }
}
