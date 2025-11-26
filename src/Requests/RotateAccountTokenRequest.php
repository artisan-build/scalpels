<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Request for rotating an account token.
 *
 * Uses currently authenticated token to generate new token.
 */
class RotateAccountTokenRequest extends Request
{
    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/auth/account-tokens/rotate';
    }
}
