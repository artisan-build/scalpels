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
            \Illuminate\Log\LogServiceProvider::class,
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
        // Default to test values that most tests need
        $app['config']->set('scalpels.url', 'https://test.scalpels.app');
        $app['config']->set('scalpels.project_id', '550e8400-e29b-41d4-a716-446655440000');
        $app['config']->set('scalpels.service_token', null);
        $app['config']->set('scalpels.webhook_secret', null);
        $app['config']->set('scalpels.default_account', 'default');
        $app['config']->set('scalpels.timeout', 30);
        $app['config']->set('scalpels.debug', false);

        // Configure logging to use null driver for tests
        $app['config']->set('logging.default', 'null');
        $app['config']->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => \Monolog\Handler\NullHandler::class,
        ]);
    }

    /**
     * Setup temporary TokenManager for tests.
     *
     * @return array{tempDir: string, cleanup: callable(): void}
     */
    protected function setupTempTokenManager(): array
    {
        $tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
        mkdir($tempDir);

        $this->app->singleton(
            \ArtisanBuild\Scalpels\Support\TokenManager::class,
            fn (): \ArtisanBuild\Scalpels\Support\TokenManager => new \ArtisanBuild\Scalpels\Support\TokenManager($tempDir)
        );

        return [
            'tempDir' => $tempDir,
            'cleanup' => function () use ($tempDir): void {
                if (is_dir($tempDir)) {
                    array_map(unlink(...), glob("{$tempDir}/.scalpels/*") ?: []);
                    if (is_dir("{$tempDir}/.scalpels")) {
                        rmdir("{$tempDir}/.scalpels");
                    }
                    rmdir($tempDir);
                }
            },
        ];
    }

    /**
     * Setup temporary ConfigManager for tests.
     *
     * @return array{tempDir: string, envPath: string, envExamplePath: string, cleanup: callable(): void}
     */
    protected function setupTempConfigManager(): array
    {
        $tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
        mkdir($tempDir);

        $envPath = "{$tempDir}/.env";
        $envExamplePath = "{$tempDir}/.env.example";

        // Create initial .env and .env.example files
        file_put_contents($envPath, "APP_NAME=TestApp\n");
        file_put_contents($envExamplePath, "APP_NAME=\n");

        $this->app->singleton(
            \ArtisanBuild\Scalpels\Support\ConfigManager::class,
            fn (): \ArtisanBuild\Scalpels\Support\ConfigManager => new \ArtisanBuild\Scalpels\Support\ConfigManager($tempDir)
        );

        return [
            'tempDir' => $tempDir,
            'envPath' => $envPath,
            'envExamplePath' => $envExamplePath,
            'cleanup' => function () use ($tempDir): void {
                if (is_dir($tempDir)) {
                    $files = glob("{$tempDir}/*") ?: [];
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    $files = glob("{$tempDir}/.env*") ?: [];
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    rmdir($tempDir);
                }
            },
        ];
    }
}
