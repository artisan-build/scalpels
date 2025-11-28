<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Support;

use RuntimeException;

/**
 * ConfigManager handles .env file reading and atomic updates.
 *
 * Manages .env file operations with atomic writes, preserving comments and formatting.
 * Updates both .env and .env.example files to keep them in sync.
 */
class ConfigManager
{
    /**
     * Base directory path for .env files.
     */
    private readonly string $basePath;

    /**
     * Create a new ConfigManager instance.
     *
     * @param  string|null  $basePath  Optional base path override for testing
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? base_path();
    }

    /**
     * Read value from .env file.
     */
    public function get(string $key): ?string
    {
        $envPath = $this->getEnvPath();

        if (! file_exists($envPath)) {
            return null;
        }

        $content = file_get_contents($envPath);
        if ($content === false) {
            return null;
        }

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if ($line === '' || $line === '0' || str_starts_with($line, '#')) {
                continue;
            }

            // Parse KEY=VALUE
            if (str_contains($line, '=')) {
                [$lineKey, $lineValue] = explode('=', $line, 2);
                $lineKey = trim($lineKey);

                if ($lineKey === $key) {
                    return $this->unquoteValue(trim($lineValue));
                }
            }
        }

        return null;
    }

    /**
     * Update value in both .env and .env.example.
     */
    public function set(string $key, string $value): void
    {
        $this->setMultiple([$key => $value]);
    }

    /**
     * Atomic update of multiple values in both .env and .env.example.
     *
     * @param  array<string, string>  $values
     */
    public function setMultiple(array $values): void
    {
        // Update .env file
        $this->updateFile($this->getEnvPath(), $values, preserveValues: true);

        // Update .env.example file
        $this->updateFile($this->getEnvExamplePath(), $values, preserveValues: false);
    }

    /**
     * Check if key exists in .env file.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get .env file path.
     */
    public function getEnvPath(): string
    {
        return "{$this->basePath}/.env";
    }

    /**
     * Get .env.example file path.
     */
    public function getEnvExamplePath(): string
    {
        return "{$this->basePath}/.env.example";
    }

    /**
     * Update a file with new values atomically.
     *
     * @param  array<string, string>  $values
     */
    private function updateFile(string $filePath, array $values, bool $preserveValues): void
    {
        // Read existing content
        $content = '';
        $existingLines = [];

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $existingLines = explode("\n", $content);
            }
        }

        // Process lines
        $updatedLines = [];
        $processedKeys = [];

        foreach ($existingLines as $line) {
            $trimmedLine = trim($line);

            // Preserve comments and empty lines
            if ($trimmedLine === '' || $trimmedLine === '0' || str_starts_with($trimmedLine, '#')) {
                $updatedLines[] = $line;

                continue;
            }

            // Parse KEY=VALUE
            if (str_contains($trimmedLine, '=')) {
                [$lineKey, $lineValue] = explode('=', $trimmedLine, 2);
                $lineKey = trim($lineKey);

                if (isset($values[$lineKey])) {
                    // Update this key
                    $newValue = $this->quoteValueIfNeeded($values[$lineKey]);
                    $updatedLines[] = "{$lineKey}={$newValue}";
                    $processedKeys[] = $lineKey;
                } elseif ($preserveValues) {
                    // Keep existing line
                    $updatedLines[] = $line;
                } else {
                    // For .env.example, clear the value
                    $updatedLines[] = "{$lineKey}=";
                }
            } else {
                // Keep line as-is
                $updatedLines[] = $line;
            }
        }

        // Add new keys that weren't in the file
        foreach ($values as $key => $value) {
            if (! in_array($key, $processedKeys, true)) {
                $newValue = $this->quoteValueIfNeeded($value);
                $updatedLines[] = "{$key}={$newValue}";
            }
        }

        // Write atomically via temp file
        $tempPath = "{$filePath}.tmp";
        $newContent = implode("\n", $updatedLines);

        if (file_put_contents($tempPath, $newContent) === false) {
            throw new RuntimeException("Failed to write to temp file: {$tempPath}");
        }

        // Atomic rename
        if (! rename($tempPath, $filePath)) {
            @unlink($tempPath);

            throw new RuntimeException("Failed to rename temp file to: {$filePath}");
        }
    }

    /**
     * Remove quotes from value if present.
     */
    private function unquoteValue(string $value): string
    {
        // Remove surrounding quotes if present
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            return substr($value, 1, -1);
        }

        return $value;
    }

    /**
     * Quote value if it contains spaces or special characters.
     */
    private function quoteValueIfNeeded(string $value): string
    {
        // Quote if value contains spaces or special characters
        if (str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"')) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
