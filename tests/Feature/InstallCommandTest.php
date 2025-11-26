<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;

beforeEach(function () {
    // Use temporary .env files for testing
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    $this->envPath = "{$this->tempDir}/.env";
    $this->envExamplePath = "{$this->tempDir}/.env.example";

    // Create initial .env and .env.example files
    file_put_contents($this->envPath, "APP_NAME=TestApp\n");
    file_put_contents($this->envExamplePath, "APP_NAME=\n");

    // Bind ConfigManager with temp directory
    $this->app->singleton(ConfigManager::class, function () {
        return new ConfigManager($this->tempDir);
    });
});

afterEach(function () {
    // Cleanup temp directory
    if (is_dir($this->tempDir)) {
        $files = glob("{$this->tempDir}/*") ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $files = glob("{$this->tempDir}/.env*") ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }
});

test('install command validates URL format', function () {
    $this->artisan('scalpels:install', [
        '--url' => 'http://example.com', // Invalid: not HTTPS
        '--project' => '550e8400-e29b-41d4-a716-446655440000',
    ])
        ->assertFailed()
        ->expectsOutputToContain('URL must use HTTPS protocol');
});

test('install command validates project UUID format', function () {
    $this->artisan('scalpels:install', [
        '--url' => 'https://example.scalpels.app',
        '--project' => 'not-a-uuid', // Invalid UUID
    ])
        ->assertFailed()
        ->expectsOutputToContain('Project must be a valid UUID');
});

test('install command writes configuration to env files', function () {
    $url = 'https://example.scalpels.app';
    $project = '550e8400-e29b-41d4-a716-446655440000';

    $this->artisan('scalpels:install', [
        '--url' => $url,
        '--project' => $project,
    ])
        ->assertSuccessful();

    // Verify .env contains values
    $envContent = file_get_contents($this->envPath);
    expect($envContent)->toContain("SCALPELS_URL={$url}")
        ->and($envContent)->toContain("SCALPELS_PROJECT={$project}");

    // Verify .env.example contains keys
    $envExampleContent = file_get_contents($this->envExamplePath);
    expect($envExampleContent)->toContain('SCALPELS_URL=')
        ->and($envExampleContent)->toContain('SCALPELS_PROJECT=');
});

test('install command displays next steps', function () {
    $this->artisan('scalpels:install', [
        '--url' => 'https://example.scalpels.app',
        '--project' => '550e8400-e29b-41d4-a716-446655440000',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Scalpels installed successfully!')
        ->expectsOutputToContain('Next steps:');
});
