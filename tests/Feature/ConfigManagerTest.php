<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;

beforeEach(function (): void {
    // Create temporary directory for .env files
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    // Create ConfigManager with test directory
    $this->configManager = new ConfigManager($this->tempDir);

    // Create initial .env file
    $envContent = <<<'ENV_WRAP'
    # Application Settings
    APP_NAME=TestApp
    APP_ENV=local

    # Database
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1

    # Scalpels Configuration
    SCALPELS_URL=https://example.scalpels.app
    SCALPELS_PROJECT=proj_test123
    ENV_WRAP;

    file_put_contents("{$this->tempDir}/.env", $envContent);

    // Create initial .env.example file
    $envExampleContent = <<<'ENV_WRAP'
    # Application Settings
    APP_NAME=
    APP_ENV=local

    # Database
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1

    # Scalpels Configuration
    SCALPELS_URL=
    SCALPELS_PROJECT=
    ENV_WRAP;

    file_put_contents("{$this->tempDir}/.env.example", $envExampleContent);
});

afterEach(function (): void {
    // Clean up temporary directory
    if (is_dir($this->tempDir)) {
        array_map(unlink(...), glob("{$this->tempDir}/*"));
        @rmdir($this->tempDir);
    }
});

test('get retrieves value from .env file', function (): void {
    expect($this->configManager->get('APP_NAME'))->toBe('TestApp')
        ->and($this->configManager->get('SCALPELS_URL'))->toBe('https://example.scalpels.app');
});

test('get returns null for non-existent key', function (): void {
    expect($this->configManager->get('NON_EXISTENT_KEY'))->toBeNull();
});

test('has returns true for existing key', function (): void {
    expect($this->configManager->has('APP_NAME'))->toBeTrue()
        ->and($this->configManager->has('NON_EXISTENT'))->toBeFalse();
});

test('set updates value in both .env and .env.example', function (): void {
    $this->configManager->set('SCALPELS_URL', 'https://new.scalpels.app');

    expect($this->configManager->get('SCALPELS_URL'))->toBe('https://new.scalpels.app');

    // Check .env.example was also updated
    $exampleContent = file_get_contents("{$this->tempDir}/.env.example");
    expect($exampleContent)->toContain('SCALPELS_URL=https://new.scalpels.app');
});

test('set preserves comments and formatting', function (): void {
    $this->configManager->set('APP_NAME', 'NewAppName');

    $envContent = file_get_contents("{$this->tempDir}/.env");
    expect($envContent)->toContain('# Application Settings')
        ->and($envContent)->toContain('# Database')
        ->and($envContent)->toContain('APP_NAME=NewAppName');
});

test('setMultiple updates multiple values atomically', function (): void {
    $this->configManager->setMultiple([
        'SCALPELS_URL' => 'https://multi.scalpels.app',
        'SCALPELS_PROJECT' => 'proj_new123',
    ]);

    expect($this->configManager->get('SCALPELS_URL'))->toBe('https://multi.scalpels.app')
        ->and($this->configManager->get('SCALPELS_PROJECT'))->toBe('proj_new123');
});

test('set handles quoted values correctly', function (): void {
    $this->configManager->set('APP_NAME', 'Value With Spaces');

    expect($this->configManager->get('APP_NAME'))->toBe('Value With Spaces');

    $envContent = file_get_contents("{$this->tempDir}/.env");
    expect($envContent)->toContain('APP_NAME="Value With Spaces"');
});

test('set adds new key if not present', function (): void {
    $this->configManager->set('NEW_KEY', 'new_value');

    expect($this->configManager->get('NEW_KEY'))->toBe('new_value');
});
