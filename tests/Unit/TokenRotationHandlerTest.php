<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenRotationHandler;
use Saloon\Contracts\Response;

beforeEach(function () {
    // Create temporary directory for .env files
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    // Create ConfigManager mock
    $this->configManager = Mockery::mock(ConfigManager::class);

    // Create TokenRotationHandler
    $this->handler = new TokenRotationHandler($this->configManager);
});

afterEach(function () {
    // Clean up
    if (is_dir($this->tempDir)) {
        array_map('unlink', glob("{$this->tempDir}/*"));
        @rmdir($this->tempDir);
    }
    Mockery::close();
});

test('detects token rotation metadata in response', function () {
    // Create mock response data
    $responseData = [
        'data' => ['some' => 'data'],
        'meta' => [
            'token_rotation_required' => true,
            'new_token' => 'svc_new123',
            'new_webhook_secret' => 'whsec_new456',
        ],
    ];

    // Mock a Response object
    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn($responseData);

    // Expect ConfigManager to be called with new values
    $this->configManager->shouldReceive('setMultiple')
        ->once()
        ->with([
            'SCALPELS_SERVICE_TOKEN' => 'svc_new123',
            'SCALPELS_WEBHOOK_SECRET' => 'whsec_new456',
        ]);

    // Invoke the handler
    ($this->handler)($response);
});

test('does not update config when rotation not required', function () {
    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn(['data' => ['some' => 'data']]);

    // ConfigManager should not be called
    $this->configManager->shouldReceive('setMultiple')->never();

    ($this->handler)($response);
});

test('validates new token format', function () {
    $responseData = [
        'data' => ['some' => 'data'],
        'meta' => [
            'token_rotation_required' => true,
            'new_token' => 'invalid_token',
            'new_webhook_secret' => 'whsec_new456',
        ],
    ];

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn($responseData);

    // ConfigManager should not be called with invalid token
    $this->configManager->shouldReceive('setMultiple')->never();

    // Handler should not throw, but log error
    ($this->handler)($response);
});

test('validates new webhook secret format', function () {
    $responseData = [
        'data' => ['some' => 'data'],
        'meta' => [
            'token_rotation_required' => true,
            'new_token' => 'svc_new123',
            'new_webhook_secret' => 'invalid_secret',
        ],
    ];

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn($responseData);

    // ConfigManager should not be called with invalid secret
    $this->configManager->shouldReceive('setMultiple')->never();

    // Handler should not throw, but log error
    ($this->handler)($response);
});

test('handles missing new_token gracefully', function () {
    $responseData = [
        'data' => ['some' => 'data'],
        'meta' => [
            'token_rotation_required' => true,
            'new_webhook_secret' => 'whsec_new456',
        ],
    ];

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn($responseData);

    $this->configManager->shouldReceive('setMultiple')->never();

    ($this->handler)($response);
});

test('handles missing new_webhook_secret gracefully', function () {
    $responseData = [
        'data' => ['some' => 'data'],
        'meta' => [
            'token_rotation_required' => true,
            'new_token' => 'svc_new123',
        ],
    ];

    $response = Mockery::mock(Response::class);
    $response->shouldReceive('json')->andReturn($responseData);

    $this->configManager->shouldReceive('setMultiple')->never();

    ($this->handler)($response);
});
