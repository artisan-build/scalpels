<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\TokenManager;

beforeEach(function (): void {
    $this->tempDir = sys_get_temp_dir().'/scalpels-edge-'.uniqid();
    mkdir($this->tempDir);
    $this->tokenManager = new TokenManager($this->tempDir);
});

afterEach(function (): void {
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

test('getAccountToken returns null for corrupted JSON file', function (): void {
    // Create .scalpels directory
    $scalpelsDir = "{$this->tempDir}/.scalpels";
    mkdir($scalpelsDir, 0700, true);

    // Write invalid JSON
    file_put_contents("{$scalpelsDir}/corrupted.json", '{invalid json content');

    // Should return null instead of throwing exception
    $result = $this->tokenManager->getAccountToken('corrupted');
    expect($result)->toBeNull();
});

test('storeAccountToken handles directory creation failure gracefully', function (): void {
    // Create a file where the directory should be (prevents mkdir)
    touch("{$this->tempDir}/.scalpels");

    // This should throw an exception when trying to create the directory
    expect(fn () => $this->tokenManager->storeAccountToken('test', [
        'token' => 'acc_test123test123test123test123test',
        'account_token_id' => 123,
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]))->toThrow(Exception::class);
});

test('switchAccount handles missing symlink directory', function (): void {
    // Store an account
    $this->tokenManager->storeAccountToken('test', [
        'token' => 'acc_test123test123test123test123test',
        'account_token_id' => 123,
        'expires_at' => '2026-02-08T10:30:00Z',
        'created_at' => '2025-11-26T10:30:00Z',
    ]);

    // Remove .scalpels directory and its contents
    $scalpelsDir = "{$this->tempDir}/.scalpels";
    $files = glob("{$scalpelsDir}/*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    rmdir($scalpelsDir);

    // switchAccount should not throw error even if directory is missing
    expect(fn () => $this->tokenManager->switchAccount('test'))
        ->not->toThrow(Exception::class);
});

test('listAccounts returns empty array when .scalpels directory does not exist', function (): void {
    $result = $this->tokenManager->listAccounts();
    expect($result)->toBe([]);
});

test('getCurrentAccount returns null when current symlink does not exist', function (): void {
    $result = $this->tokenManager->getCurrentAccount();
    expect($result)->toBeNull();
});

test('deleteAccount handles non-existent account gracefully', function (): void {
    // Should not throw exception
    expect(fn () => $this->tokenManager->deleteAccount('nonexistent'))
        ->not->toThrow(Exception::class);
});
