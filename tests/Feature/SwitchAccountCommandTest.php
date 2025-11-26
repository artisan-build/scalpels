<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\TokenManager;

beforeEach(function () {
    // Use temporary directory for token storage
    $this->tempDir = sys_get_temp_dir().'/scalpels-test-'.uniqid();
    mkdir($this->tempDir);

    $this->app->singleton(TokenManager::class, function () {
        return new TokenManager($this->tempDir);
    });

    // Create test account tokens
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenManager->storeAccountToken('default', [
        'account_token_id' => 123,
        'token' => 'acc_default123456789012345678901234',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);
    $tokenManager->storeAccountToken('client-two', [
        'account_token_id' => 456,
        'token' => 'acc_client2123456789012345678901234',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);
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

test('switch account command changes current account', function () {
    $this->artisan('scalpels:switch-account', ['account' => 'client-two'])
        ->assertSuccessful()
        ->expectsOutput('Switched to account: client-two');

    // Verify account was switched
    $tokenManager = $this->app->make(TokenManager::class);
    expect($tokenManager->getCurrentAccount())->toBe('client-two');
});

test('switch account command validates account exists', function () {
    $this->artisan('scalpels:switch-account', ['account' => 'nonexistent'])
        ->assertFailed()
        ->expectsOutput('Account "nonexistent" does not exist');
});

test('switch account command displays interactive menu without argument', function () {
    $this->artisan('scalpels:switch-account')
        ->expectsChoice('Select account', 'client-two', ['default', 'client-two'])
        ->assertSuccessful()
        ->expectsOutput('Switched to account: client-two');
});

test('switch account command handles no accounts error', function () {
    // Remove all accounts
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenManager->deleteAccount('default');
    $tokenManager->deleteAccount('client-two');

    $this->artisan('scalpels:switch-account')
        ->assertFailed()
        ->expectsOutput('No accounts found. Please run: php artisan scalpels:auth');
});

test('switch account command displays expiration info', function () {
    $this->artisan('scalpels:switch-account', ['account' => 'client-two'])
        ->assertSuccessful()
        ->expectsOutputToContain('Expires: 2026-02-08');
});
