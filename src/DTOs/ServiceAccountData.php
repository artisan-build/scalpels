<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\DTOs;

/**
 * Data transfer object for service account storage.
 *
 * Provides immutable structure for service account data.
 */
readonly class ServiceAccountData
{
    /**
     * Create a new service account data instance.
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
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_token' => $this->serviceToken,
            'webhook_secret' => $this->webhookSecret,
            'project_id' => $this->projectId,
            'environment' => $this->environment,
            'environment_type' => $this->environmentType,
            'name' => $this->name,
            'created_at' => $this->createdAt,
        ];
    }
}
