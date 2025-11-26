<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Request for triggering service token rotation.
 *
 * Sets requires_rotation flag on service account.
 */
class TriggerServiceTokenRotationRequest extends Request
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
        return "/api/v1/auth/service-tokens/{$this->serviceAccountId}/rotate";
    }
}
