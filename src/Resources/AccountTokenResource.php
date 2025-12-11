<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Resources;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
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
        public ?Carbon $expiresAt,
        public Carbon $createdAt,
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
            expiresAt: isset($data['expires_at']) ? Date::parse($data['expires_at']) : null,
            createdAt: Date::parse($data['created_at']),
        );
    }
}
