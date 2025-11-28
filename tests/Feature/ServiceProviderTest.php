<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Providers\ScalpelsServiceProvider;
use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenManager;

test('service provider is discoverable via composer extra', function (): void {
    $composerPath = __DIR__.'/../../composer.json';

    expect($composerPath)->toBeFile();

    $composer = json_decode(file_get_contents($composerPath), true);

    expect($composer)
        ->toHaveKey('extra.laravel.providers')
        ->and($composer['extra']['laravel']['providers'])
        ->toContain(ScalpelsServiceProvider::class);
});

test('service provider registers configuration file', function (): void {
    expect(config('scalpels.url'))->toBeNull()
        ->and(config('scalpels.project_id'))->toBeNull()
        ->and(config('scalpels.service_token'))->toBeNull()
        ->and(config('scalpels.webhook_secret'))->toBeNull()
        ->and(config('scalpels.default_account'))->toBe('default')
        ->and(config('scalpels.timeout'))->toBe(30)
        ->and(config('scalpels.debug'))->toBeFalse();
});

test('service provider binds TokenManager as singleton', function (): void {
    $instance1 = app(TokenManager::class);
    $instance2 = app(TokenManager::class);

    expect($instance1)
        ->toBeInstanceOf(TokenManager::class)
        ->toBe($instance2);
});

test('service provider binds ConfigManager as singleton', function (): void {
    $instance1 = app(ConfigManager::class);
    $instance2 = app(ConfigManager::class);

    expect($instance1)
        ->toBeInstanceOf(ConfigManager::class)
        ->toBe($instance2);
});

test('package namespace resolves correctly', function (): void {
    expect(class_exists(ScalpelsServiceProvider::class))->toBeTrue();
});
