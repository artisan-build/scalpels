<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Resources;

use Saloon\Http\Response;

/**
 * Resource class for account token responses.
 *
 * Parses API responses for account token creation.
 */
readonly class AccountTokenResource
{
    /**
     * Create a new account token resource instance.
     */
    public function __construct(
        public int $id,
        public string $token,
        public ?string $expiresAt,
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
            token: $data['token'],
            expiresAt: $data['expires_at'] ?? null,
            createdAt: $data['created_at'],
        );
    }
}
