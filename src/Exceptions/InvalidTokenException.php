<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Exceptions;

use Exception;

/**
 * Exception thrown when a token format is invalid or does not match expected prefix.
 *
 * Used for validating account tokens (acc_ prefix), service tokens (svc_ prefix),
 * and context tokens (ctx_ prefix) to ensure they conform to the expected format
 * before making API calls or storing tokens.
 */
class InvalidTokenException extends Exception
{
    /**
     * Create exception for invalid token prefix.
     */
    public static function invalidPrefix(string $token, string $expectedPrefix): self
    {
        $actualPrefix = substr($token, 0, 4);

        return new self(
            "Invalid token prefix. Expected token to start with '{$expectedPrefix}' but got '{$actualPrefix}'."
        );
    }

    /**
     * Create exception for invalid token length.
     */
    public static function invalidLength(string $token, int $expectedLength): self
    {
        $actualLength = strlen($token);

        return new self(
            "Invalid token length. Expected {$expectedLength} characters but got {$actualLength}."
        );
    }

    /**
     * Create exception for empty token.
     */
    public static function emptyToken(): self
    {
        return new self('Token cannot be empty.');
    }
}
