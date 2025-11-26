<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Resources;

use Saloon\Http\Response;

/**
 * Resource class for service account responses.
 *
 * Parses API responses for service token creation.
 */
readonly class ServiceAccountResource
{
    /**
     * Create a new service account resource instance.
     */
    public function __construct(
        public int $id,
        public string $serviceToken,
        public string $webhookSecret,
        public string $projectId,
        public string $environment,
        public string $environmentType,
        public ?string $name,
        public string $createdAt,
    ) {}

    /**
     * Create resource from API response.
     */
    public static function fromResponse(Response $response): self
    {
        $data = $response->json('data');

        return new self(
            id: $data['id'],
            serviceToken: $data['service_token'],
            webhookSecret: $data['webhook_secret'],
            projectId: $data['project_id'],
            environment: $data['environment'],
            environmentType: $data['environment_type'],
            name: $data['name'] ?? null,
            createdAt: $data['created_at'],
        );
    }
}
