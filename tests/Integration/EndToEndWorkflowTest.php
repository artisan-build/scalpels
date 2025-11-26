<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenManager;

beforeEach(function () {
    // Use temporary directory for all filesystem operations
    $this->tempDir = sys_get_temp_dir().'/scalpels-integration-'.uniqid();
    mkdir($this->tempDir);
    mkdir("{$this->tempDir}/.env", 0755, true);

    // Create .env file
    file_put_contents("{$this->tempDir}/.env/.env", "APP_NAME=TestApp\n");
    file_put_contents("{$this->tempDir}/.env/.env.example", "APP_NAME=TestApp\n");

    // Bind TokenManager with custom home directory
    $this->app->singleton(TokenManager::class, function () {
        return new TokenManager($this->tempDir);
    });

    // Bind ConfigManager with custom env path
    $this->app->singleton(ConfigManager::class, function () {
        return new ConfigManager("{$this->tempDir}/.env");
    });
});

afterEach(function () {
    // Cleanup temp directory
    if (is_dir($this->tempDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        rmdir($this->tempDir);
    }
});

test('complete authentication workflow stores and retrieves token', function () {
    // Step 1: Install command configures project
    $configManager = $this->app->make(ConfigManager::class);
    $configManager->set('SCALPELS_URL', 'https://test.scalpels.app');
    $configManager->set('SCALPELS_PROJECT', '550e8400-e29b-41d4-a716-446655440000');

    expect($configManager->get('SCALPELS_URL'))->toBe('https://test.scalpels.app');

    // Step 2: Auth command creates account token
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenManager->storeAccountToken('default', [
        'account_token_id' => 123,
        'token' => 'acc_abcdef1234567890abcdef1234567890',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);

    // Step 3: Token is retrievable
    $token = $tokenManager->resolveToken();
    expect($token)->toBe('acc_abcdef1234567890abcdef1234567890');

    // Step 4: Verify file permissions
    $tokenFile = "{$this->tempDir}/.scalpels/default.json";
    expect(file_exists($tokenFile))->toBeTrue();

    // Check permissions (0600 = owner read/write only)
    $perms = fileperms($tokenFile) & 0777;
    expect($perms)->toBe(0600);
});

test('service token generation and rotation workflow', function () {
    // Step 1: Authenticate with account token
    $tokenManager = $this->app->make(TokenManager::class);
    $tokenManager->storeAccountToken('default', [
        'account_token_id' => 123,
        'token' => 'acc_abcdef1234567890abcdef1234567890',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);

    // Step 2: Generate service token (simulated)
    $configManager = $this->app->make(ConfigManager::class);
    $configManager->set('SCALPELS_SERVICE_TOKEN', 'svc_xyz789xyz789xyz789xyz789xyz789xyz');
    $configManager->set('SCALPELS_WEBHOOK_SECRET', 'whsec_secret123secret123secret123secret');

    // Step 3: Service token takes priority
    $token = $tokenManager->resolveToken();
    expect($token)->toBe('svc_xyz789xyz789xyz789xyz789xyz789xyz');

    // Step 4: Simulate rotation
    $configManager->setMultiple([
        'SCALPELS_SERVICE_TOKEN' => 'svc_newtoken123newtoken123newtoken1234',
        'SCALPELS_WEBHOOK_SECRET' => 'whsec_newsecret123newsecret123newsecr',
    ]);

    // Step 5: New token is active
    $newToken = $tokenManager->resolveToken();
    expect($newToken)->toBe('svc_newtoken123newtoken123newtoken1234');
});

test('multiple account switching workflow', function () {
    $tokenManager = $this->app->make(TokenManager::class);

    // Step 1: Create first account
    $tokenManager->storeAccountToken('client-one', [
        'account_token_id' => 123,
        'token' => 'acc_client1client1client1client1client',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);

    // Step 2: Create second account
    $tokenManager->storeAccountToken('client-two', [
        'account_token_id' => 456,
        'token' => 'acc_client2client2client2client2client',
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);

    // Step 3: Switch to first account
    $tokenManager->switchAccount('client-one');
    expect($tokenManager->getCurrentAccount())->toBe('client-one');
    expect($tokenManager->resolveToken())->toBe('acc_client1client1client1client1client');

    // Step 4: Switch to second account
    $tokenManager->switchAccount('client-two');
    expect($tokenManager->getCurrentAccount())->toBe('client-two');
    expect($tokenManager->resolveToken())->toBe('acc_client2client2client2client2client');

    // Step 5: List all accounts
    $accounts = $tokenManager->listAccounts();
    expect($accounts)->toContain('client-one', 'client-two');
});

test('configuration file updates preserve formatting', function () {
    $configManager = $this->app->make(ConfigManager::class);

    // Step 1: Create .env with specific formatting
    file_put_contents("{$this->tempDir}/.env/.env", <<<'ENV'
# Application Configuration
APP_NAME="Test App"
APP_ENV=local

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1

# Scalpels Configuration will be added below
ENV
    );

    // Step 2: Add Scalpels configuration
    $configManager->set('SCALPELS_URL', 'https://test.scalpels.app');
    $configManager->set('SCALPELS_PROJECT', '550e8400-e29b-41d4-a716-446655440000');

    // Step 3: Read file and verify formatting preserved
    $content = file_get_contents("{$this->tempDir}/.env/.env");

    expect($content)->toContain('# Application Configuration')
        ->and($content)->toContain('# Database Configuration')
        ->and($content)->toContain('SCALPELS_URL=https://test.scalpels.app')
        ->and($content)->toContain('SCALPELS_PROJECT=550e8400-e29b-41d4-a716-446655440000');
});
