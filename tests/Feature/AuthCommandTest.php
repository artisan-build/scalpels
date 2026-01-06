<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Support\TokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

test('auth command requires configuration', function (): void {
    // Clear configuration
    resolve('config')->set('scalpels.url', null);
    resolve('config')->set('scalpels.project_id', null);

    $this->artisan('scalpels:auth')
        ->assertFailed()
        ->expectsOutputToContain('Scalpels not configured');
});

test('auth command stores account token on success', function (): void {
    // Setup TokenManager with temp directory
    $tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($tempDir);
    app()->singleton(TokenManager::class, fn (): TokenManager => new TokenManager($tempDir));

    // Mock successful authentication response
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 123,
                'token' => 'acc_abcdef1234567890abcdef1234567890',
                'expires_at' => '2026-02-08T10:30:00Z',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = resolve(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertSuccessful()
        ->expectsOutputToContain('Authentication successful!');

    // Verify token was stored
    $tokenManager = resolve(TokenManager::class);
    $tokenData = $tokenManager->getAccountToken('default');
    expect($tokenData)->not->toBeNull()
        ->and($tokenData['token'])->toBe('acc_abcdef1234567890abcdef1234567890');

    // Cleanup
    if (is_dir($tempDir)) {
        array_map(unlink(...), glob("{$tempDir}/.scalpels/*") ?: []);
        if (is_dir("{$tempDir}/.scalpels")) {
            rmdir("{$tempDir}/.scalpels");
        }
        rmdir($tempDir);
    }
});

test('auth command handles invalid credentials', function (): void {
    $setup = setupTempTokenManager();

    // Mock authentication failure
    $mockClient = new MockClient([
        MockResponse::make([
            'error' => 'Invalid credentials',
            'code' => 'INVALID_CREDENTIALS',
        ], 401),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'wrongpass')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertFailed()
        ->expectsOutputToContain('Invalid credentials');

    $setup['cleanup']();
});

test('auth command handles 2FA required error', function (): void {
    $setup = setupTempTokenManager();

    // Mock 2FA required response
    $mockClient = new MockClient([
        MockResponse::make([
            'error' => 'Two-factor authentication is required',
            'code' => 'TWO_FACTOR_REQUIRED',
        ], 403),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertFailed()
        ->expectsOutputToContain('Two-factor authentication is required')
        ->expectsOutputToContain('https://test.scalpels.app/settings');

    $setup['cleanup']();
});

test('auth command supports named accounts', function (): void {
    $setup = setupTempTokenManager();

    // Mock successful authentication
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 456,
                'token' => 'acc_xyz789xyz789xyz789xyz789xyz789xyz',
                'expires_at' => '2026-02-08T10:30:00Z',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth', ['--name' => 'client-two'])
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertSuccessful();

    // Verify token was stored with custom name
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenData = $tokenManager->getAccountToken('client-two');
    expect($tokenData)->not->toBeNull()
        ->and($tokenData['token'])->toBe('acc_xyz789xyz789xyz789xyz789xyz789xyz');

    $setup['cleanup']();
});

test('auth command supports expiration days option', function (): void {
    $setup = setupTempTokenManager();

    // Mock successful authentication with expiration
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 123,
                'token' => 'acc_abcdef1234567890abcdef1234567890',
                'expires_at' => '2025-12-26T10:30:00Z',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth', ['--expires-in-days' => 30])
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertSuccessful()
        ->expectsOutputToContain('Expires: 2025-12-26');

    $setup['cleanup']();
});
