<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Connectors;

use ArtisanBuild\Scalpels\Support\TokenManager;
use ArtisanBuild\Scalpels\Support\TokenRotationHandler;
use Saloon\Http\Connector;
use Saloon\Http\PendingRequest;
use Saloon\Traits\Plugins\AcceptsJson;

/**
 * Saloon connector for Scalpels API.
 *
 * Automatically injects Bearer authentication and registers
 * response interceptor for token rotation detection.
 */
class ScalpelsConnector extends Connector
{
    use AcceptsJson;

    /**
     * Create a new connector instance.
     */
    public function __construct(
        protected TokenManager $tokenManager,
        protected TokenRotationHandler $tokenRotationHandler,
    ) {}

    /**
     * Resolve the base URL for API requests.
     */
    public function resolveBaseUrl(): string
    {
        return config('scalpels.url');
    }

    /**
     * Define default headers for all requests.
     *
     * Injects Bearer token from TokenManager.
     *
     * @return array<string, string>
     */
    public function defaultHeaders(): array
    {
        $token = $this->tokenManager->resolveToken();

        if ($token === null) {
            // For CreateAccountTokenRequest, authentication is not required
            // The request itself creates the authentication token
            return [];
        }

        return [
            'Authorization' => "Bearer {$token}",
        ];
    }

    /**
     * Boot the connector.
     *
     * Registers TokenRotationHandler as response interceptor.
     */
    public function boot(PendingRequest $pendingRequest): void
    {
        $pendingRequest->middleware()->onResponse($this->tokenRotationHandler);
    }
}
