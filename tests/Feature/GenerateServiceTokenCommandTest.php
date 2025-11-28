<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Support\TokenManager;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

beforeEach(function (): void {
    // Configure Scalpels
    config(['scalpels.url' => 'https://test.scalpels.app']);
    config(['scalpels.project_id' => '550e8400-e29b-41d4-a716-446655440000']);

    // Use temporary directory for token storage
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    $this->app->singleton(TokenManager::class, fn (): TokenManager => new TokenManager($this->tempDir));

    // Store account token for authentication
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenManager->storeAccountToken('default', [
        'account_token_id' => 123,
        'token' => 'acc_default123456789012345678901234',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);
});

afterEach(function (): void {
    // Cleanup temp directory
    if (is_dir($this->tempDir)) {
        array_map(unlink(...), glob("{$this->tempDir}/.scalpels/*"));
        if (is_dir("{$this->tempDir}/.scalpels")) {
            rmdir("{$this->tempDir}/.scalpels");
        }
        rmdir($this->tempDir);
    }
});

test('generate service token command creates token successfully', function (): void {
    // Mock successful service token creation
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 789,
                'service_token' => 'svc_abcdef1234567890abcdef1234567890',
                'webhook_secret' => 'whsec_xyz789xyz789xyz789xyz789xyz789xyz',
                'environment' => 'production',
                'environment_type' => 'production',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'production',
        'type' => 'production',
    ])
        ->assertSuccessful()
        ->expectsOutput('Service token created successfully!')
        ->expectsOutputToContain('svc_abcdef1234567890abcdef1234567890')
        ->expectsOutputToContain('whsec_xyz789xyz789xyz789xyz789xyz789xyz')
        ->expectsOutputToContain('SCALPELS_SERVICE_TOKEN')
        ->expectsOutputToContain('SCALPELS_WEBHOOK_SECRET');
});

test('generate service token command validates environment type', function (): void {
    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'production',
        'type' => 'invalid-type',
    ])
        ->assertFailed()
        ->expectsOutput('Environment type must be one of: production, staging, preview, development, testing');
});

test('generate service token command handles 2FA required error', function (): void {
    // Mock 2FA required response
    $mockClient = new MockClient([
        MockResponse::make([
            'error' => 'Two-factor authentication is required',
            'code' => 'TWO_FACTOR_REQUIRED',
        ], 403),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'production',
        'type' => 'production',
    ])
        ->assertFailed()
        ->expectsOutputToContain('Two-factor authentication is required')
        ->expectsOutputToContain('https://test.scalpels.app/settings');
});

test('generate service token command handles invalid operation error', function (): void {
    // Mock invalid operation response (service token creating service token)
    $mockClient = new MockClient([
        MockResponse::make([
            'error' => 'Service tokens cannot create other service tokens',
            'code' => 'INVALID_OPERATION',
        ], 400),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'production',
        'type' => 'production',
    ])
        ->assertFailed()
        ->expectsOutput('Service tokens cannot create other service tokens. Please use an account token.');
});

test('generate service token command supports custom name', function (): void {
    // Mock successful service token creation
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 789,
                'service_token' => 'svc_abcdef1234567890abcdef1234567890',
                'webhook_secret' => 'whsec_xyz789xyz789xyz789xyz789xyz789xyz',
                'environment' => 'staging',
                'environment_type' => 'staging',
                'name' => 'Custom Staging Token',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'staging',
        'type' => 'staging',
        '--name' => 'Custom Staging Token',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Custom Staging Token');
});

test('generate service token command warns to store credentials securely', function (): void {
    // Mock successful service token creation
    $mockClient = new MockClient([
        MockResponse::make([
            'data' => [
                'id' => 789,
                'service_token' => 'svc_abcdef1234567890abcdef1234567890',
                'webhook_secret' => 'whsec_xyz789xyz789xyz789xyz789xyz789xyz',
                'environment' => 'production',
                'environment_type' => 'production',
                'created_at' => '2025-11-26T10:30:00Z',
            ],
        ], 201),
    ]);

    $connector = $this->app->make(ScalpelsConnector::class);
    $connector->withMockClient($mockClient);

    $this->artisan('scalpels:generate-service-token', [
        'environment' => 'production',
        'type' => 'production',
    ])
        ->assertSuccessful()
        ->expectsOutputToContain('Store these credentials securely');
});
