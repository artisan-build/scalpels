<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Exceptions;

use Exception;

/**
 * Exception thrown when authentication with the Scalpels API fails.
 *
 * Used for invalid credentials, missing tokens, expired tokens, revoked tokens,
 * and 2FA requirements during authentication flows.
 */
class AuthenticationException extends Exception
{
    /**
     * Create exception for missing authentication token.
     */
    public static function noTokenAvailable(): self
    {
        return new self(
            'No authentication token available. Please run "php artisan scalpels:auth" to authenticate.'
        );
    }

    /**
     * Create exception for invalid credentials.
     */
    public static function invalidCredentials(): self
    {
        return new self('Invalid email or password. Please check your credentials and try again.');
    }

    /**
     * Create exception for 2FA requirement.
     */
    public static function twoFactorRequired(string $settingsUrl): self
    {
        return new self(
            "Two-factor authentication is required. Please enable 2FA at: {$settingsUrl}"
        );
    }

    /**
     * Create exception for expired token.
     */
    public static function tokenExpired(): self
    {
        return new self(
            'Your authentication token has expired. Please run "php artisan scalpels:auth" to re-authenticate.'
        );
    }

    /**
     * Create exception for revoked token.
     */
    public static function tokenRevoked(): self
    {
        return new self(
            'Your authentication token has been revoked. Please run "php artisan scalpels:auth" to re-authenticate.'
        );
    }

    /**
     * Create exception for insufficient scope.
     */
    public static function insufficientScope(string $requiredScope): self
    {
        return new self(
            "Insufficient permissions. This operation requires '{$requiredScope}' scope."
        );
    }

    /**
     * Create exception for API error response.
     */
    public static function apiError(string $message, string $code): self
    {
        return new self("API authentication error ({$code}): {$message}");
    }
}
