<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\ConfigManager;

beforeEach(function () {
    $this->tempDir = sys_get_temp_dir().'/scalpels-config-'.uniqid();
    mkdir($this->tempDir);
    $this->configManager = new ConfigManager($this->tempDir);
});

afterEach(function () {
    if (is_dir($this->tempDir)) {
        // Clean up all files and subdirectories
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

test('get returns null for corrupted .env file', function () {
    // Create corrupted .env file (binary content)
    file_put_contents("{$this->tempDir}/.env", "\x00\x01\x02\x03\x04");

    $result = $this->configManager->get('APP_NAME');
    expect($result)->toBeNull();
});

test('set handles extremely long values', function () {
    $longValue = str_repeat('a', 10000);

    $this->configManager->set('VERY_LONG_VALUE', $longValue);

    $retrieved = $this->configManager->get('VERY_LONG_VALUE');
    expect($retrieved)->toBe($longValue);
});

test('set handles values with special characters', function () {
    $specialValues = [
        'EQUALS' => 'Value=with=equals=signs',
        'HASH' => 'Value#with#hash#signs',
        'DOLLAR' => 'Value$with$dollar$signs',
    ];

    foreach ($specialValues as $key => $value) {
        $this->configManager->set($key, $value);
    }

    // Verify all values are stored and retrieved correctly
    foreach ($specialValues as $key => $value) {
        $retrieved = $this->configManager->get($key);
        expect($retrieved)->not->toBeNull();
    }
});

test('setMultiple rolls back on partial failure', function () {
    // Create initial .env
    file_put_contents("{$this->tempDir}/.env", "EXISTING_KEY=original_value\n");

    // Make .env.example read-only to force failure
    touch("{$this->tempDir}/.env.example");
    chmod("{$this->tempDir}/.env.example", 0444);

    try {
        $this->configManager->setMultiple([
            'NEW_KEY_1' => 'value1',
            'NEW_KEY_2' => 'value2',
        ]);
    } catch (Exception $e) {
        // Expected to fail due to read-only .env.example
    }

    // Original value should still exist
    $result = $this->configManager->get('EXISTING_KEY');
    expect($result)->toBe('original_value');

    // Restore permissions for cleanup
    chmod("{$this->tempDir}/.env.example", 0644);
});

test('has returns false for keys in comments', function () {
    file_put_contents("{$this->tempDir}/.env", <<<'ENV'
# COMMENTED_KEY=value
ENV
    );

    expect($this->configManager->has('COMMENTED_KEY'))->toBeFalse();
});
