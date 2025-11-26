<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenManager;
use ArtisanBuild\Scalpels\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Setup temporary TokenManager for tests.
 *
 * @return array{tempDir: string, cleanup: callable(): void}
 */
function setupTempTokenManager(): array
{
    $tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($tempDir);

    test()->app->singleton(
        TokenManager::class,
        fn (): TokenManager => new TokenManager($tempDir)
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
function setupTempConfigManager(): array
{
    $tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($tempDir);

    $envPath = "{$tempDir}/.env";
    $envExamplePath = "{$tempDir}/.env.example";

    // Create initial .env and .env.example files
    file_put_contents($envPath, "APP_NAME=TestApp\n");
    file_put_contents($envExamplePath, "APP_NAME=\n");

    test()->app->singleton(
        ConfigManager::class,
        fn (): ConfigManager => new ConfigManager($tempDir)
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
