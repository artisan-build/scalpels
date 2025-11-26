<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Exceptions;

use Exception;

/**
 * Exception thrown when configuration file operations fail.
 *
 * Used for file system errors when reading, writing, or updating .env files,
 * as well as validation errors for configuration values like URLs and project IDs.
 */
class ConfigurationException extends Exception
{
    /**
     * Create exception for missing configuration file.
     */
    public static function fileNotFound(string $path): self
    {
        return new self("Configuration file not found at: {$path}");
    }

    /**
     * Create exception for unreadable configuration file.
     */
    public static function fileNotReadable(string $path): self
    {
        return new self("Configuration file is not readable: {$path}");
    }

    /**
     * Create exception for unwritable configuration file.
     */
    public static function fileNotWritable(string $path): self
    {
        return new self("Configuration file is not writable: {$path}");
    }

    /**
     * Create exception for failed atomic write operation.
     */
    public static function atomicWriteFailed(string $path): self
    {
        return new self("Failed to atomically write configuration file: {$path}");
    }

    /**
     * Create exception for missing required configuration key.
     */
    public static function missingKey(string $key): self
    {
        return new self("Required configuration key is missing: {$key}");
    }

    /**
     * Create exception for invalid configuration value.
     */
    public static function invalidValue(string $key, string $reason): self
    {
        return new self("Invalid configuration value for '{$key}': {$reason}");
    }
}
