<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Request for revoking an account token.
 *
 * Requires the account token ID to revoke.
 */
class RevokeAccountTokenRequest extends Request
{
    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected int $accountTokenId,
    ) {}

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return "/api/v1/auth/account-tokens/{$this->accountTokenId}/revoke";
    }
}
