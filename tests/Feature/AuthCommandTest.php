<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Support\TokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function () {
    // Configure Scalpels
    config(['scalpels.url' => 'https://test.scalpels.app']);
    config(['scalpels.project_id' => '550e8400-e29b-41d4-a716-446655440000']);

    // Use temporary directory for token storage
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    $this->app->singleton(TokenManager::class, function () {
        return new TokenManager($this->tempDir);
    });
});

afterEach(function () {
    // Cleanup temp directory
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob("{$this->tempDir}/.scalpels/*"));
        if (is_dir("{$this->tempDir}/.scalpels")) {
            rmdir("{$this->tempDir}/.scalpels");
        }
        rmdir($this->tempDir);
    }
});

test('auth command requires configuration', function () {
    // Clear configuration
    config(['scalpels.url' => null]);
    config(['scalpels.project_id' => null]);

    $this->artisan('scalpels:auth')
        ->assertFailed()
        ->expectsOutputToContain('Scalpels not configured');
});

test('auth command stores account token on success', function () {
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

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:auth')
        ->expectsQuestion('Email', 'test@example.com')
        ->expectsQuestion('Password', 'secret123')
        ->expectsQuestion('Two-factor code (press Enter if not enabled)', '')
        ->assertSuccessful()
        ->expectsOutputToContain('Authentication successful!');

    // Verify token was stored
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenData = $tokenManager->getAccountToken('default');
    expect($tokenData)->not->toBeNull()
        ->and($tokenData['token'])->toBe('acc_abcdef1234567890abcdef1234567890');
});

test('auth command handles invalid credentials', function () {
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
});

test('auth command handles 2FA required error', function () {
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
});

test('auth command supports named accounts', function () {
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
});

test('auth command supports expiration days option', function () {
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
});
