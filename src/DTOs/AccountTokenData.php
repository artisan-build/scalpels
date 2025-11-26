<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\DTOs;

/**
 * Data transfer object for account token storage.
 *
 * Provides immutable structure for account token data
 * stored in ~/.scalpels/{account-name}.json files.
 */
readonly class AccountTokenData
{
    /**
     * Create a new account token data instance.
     */
    public function __construct(
        public int $accountTokenId,
        public string $token,
        public ?string $expiresAt,
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
            'account_token_id' => $this->accountTokenId,
            'token' => $this->token,
            'expires_at' => $this->expiresAt,
            'created_at' => $this->createdAt,
        ];
    }
}
