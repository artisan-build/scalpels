<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Request for revoking a service token.
 *
 * Requires the service account ID to revoke.
 */
class RevokeServiceTokenRequest extends Request
{
    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected int $serviceAccountId,
    ) {}

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return "/api/v1/auth/service-tokens/{$this->serviceAccountId}/revoke";
    }
}
