<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Request for creating a service token.
 *
 * Requires project ID, environment, environment type, and optional name.
 */
class CreateServiceTokenRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected string $projectId,
        protected string $environment,
        protected string $environmentType,
        protected ?string $name = null,
    ) {}

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/auth/service-tokens';
    }

    /**
     * Define the default body for this request.
     *
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return [
            'project_id' => $this->projectId,
            'environment' => $this->environment,
            'environment_type' => $this->environmentType,
            'name' => $this->name,
        ];
    }
}
