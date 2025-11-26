<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Support;

/**
 * WebhookSigner handles HMAC-SHA256 signature computation and verification.
 *
 * Uses constant-time comparison for security when verifying webhook signatures.
 */
class WebhookSigner
{
    /**
     * Compute HMAC-SHA256 signature for payload.
     */
    public function sign(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Verify webhook signature using constant-time comparison.
     */
    public function verify(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = $this->sign($payload, $secret);

        // Use constant-time comparison to prevent timing attacks
        return hash_equals($expectedSignature, $signature);
    }
}
