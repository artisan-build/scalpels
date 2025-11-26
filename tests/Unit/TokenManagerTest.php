<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Exceptions\InvalidTokenException;
use ArtisanBuild\Scalpels\Support\TokenManager;

beforeEach(function () {
    // Create temporary home directory for testing
    $this->tempHome = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempHome);

    // Create TokenManager with test home directory
    $this->tokenManager = new TokenManager($this->tempHome);

    // Set up config mocks
    config(['scalpels.service_token' => null]);
});

afterEach(function () {
    // Clean up temporary directory
    if (is_dir($this->tempHome)) {
        array_map('unlink', glob("{$this->tempHome}/.scalpels/*"));
        @rmdir("{$this->tempHome}/.scalpels");
        @rmdir($this->tempHome);
    }
});

test('resolveToken returns service token when available', function () {
    config(['scalpels.service_token' => 'svc_test123']);

    expect($this->tokenManager->resolveToken())->toBe('svc_test123');
});

test('resolveToken returns account token when service token not available', function () {
    config(['scalpels.service_token' => null]);

    // Store an account token
    $this->tokenManager->storeAccountToken('default', [
        'account_token_id' => 1,
        'token' => 'acc_test123',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);

    expect($this->tokenManager->resolveToken())->toBe('acc_test123');
});

test('resolveToken returns null when no tokens available', function () {
    config(['scalpels.service_token' => null]);

    expect($this->tokenManager->resolveToken())->toBeNull();
});

test('storeAccountToken validates token format', function () {
    $this->tokenManager->storeAccountToken('default', [
        'account_token_id' => 1,
        'token' => 'invalid_token',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);
})->throws(InvalidTokenException::class, 'Account token must start with acc_');

test('storeAccountToken creates file with 0600 permissions', function () {
    $this->tokenManager->storeAccountToken('default', [
        'account_token_id' => 1,
        'token' => 'acc_test123',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);

    $filePath = "{$this->tempHome}/.scalpels/default.json";
    expect(file_exists($filePath))->toBeTrue()
        ->and(decoct(fileperms($filePath) & 0777))->toBe('600');
});

test('getAccountToken retrieves stored token', function () {
    $tokenData = [
        'account_token_id' => 1,
        'token' => 'acc_test123',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ];

    $this->tokenManager->storeAccountToken('default', $tokenData);

    $retrieved = $this->tokenManager->getAccountToken('default');
    expect($retrieved)->toMatchArray($tokenData);
});

test('listAccounts returns all stored account names', function () {
    $this->tokenManager->storeAccountToken('default', [
        'account_token_id' => 1,
        'token' => 'acc_test123',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);

    $this->tokenManager->storeAccountToken('client-two', [
        'account_token_id' => 2,
        'token' => 'acc_test456',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);

    $accounts = $this->tokenManager->listAccounts();
    expect($accounts)->toHaveCount(2)
        ->and($accounts)->toContain('default', 'client-two');
});

test('switchAccount updates current symlink', function () {
    $this->tokenManager->storeAccountToken('default', [
        'account_token_id' => 1,
        'token' => 'acc_test123',
        'expires_at' => now()->addDays(30)->toISOString(),
        'created_at' => now()->toISOString(),
    ]);

    $this->tokenManager->switchAccount('default');

    $symlinkPath = "{$this->tempHome}/.scalpels/current";
    expect(is_link($symlinkPath))->toBeTrue()
        ->and(readlink($symlinkPath))->toBe('default.json');
});
