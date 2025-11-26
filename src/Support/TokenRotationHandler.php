<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Support;

use Illuminate\Support\Facades\Log;
use Saloon\Contracts\Response;

/**
 * TokenRotationHandler intercepts Saloon responses to detect and handle token rotation.
 *
 * Automatically updates .env file with new service token and webhook secret
 * when rotation metadata is detected in API responses.
 */
class TokenRotationHandler
{
    /**
     * Create a new TokenRotationHandler instance.
     */
    public function __construct(
        private readonly ConfigManager $configManager
    ) {}

    /**
     * Handle Saloon response interception.
     *
     * Detects meta.token_rotation_required in response and updates .env file
     * with new token and webhook secret if present.
     */
    public function __invoke(Response $response): void
    {
        try {
            $data = $response->json();

            // Check if rotation is required
            if (! isset($data['meta']['token_rotation_required']) || ! $data['meta']['token_rotation_required']) {
                return;
            }

            // Extract new credentials
            $newToken = $data['meta']['new_token'] ?? null;
            $newWebhookSecret = $data['meta']['new_webhook_secret'] ?? null;

            // Validate both credentials are present
            if ($newToken === null || $newWebhookSecret === null) {
                Log::error('Token rotation metadata incomplete', [
                    'has_token' => $newToken !== null,
                    'has_secret' => $newWebhookSecret !== null,
                ]);

                return;
            }

            // Validate token format
            if (! str_starts_with($newToken, 'svc_')) {
                Log::error('Invalid service token format in rotation metadata', [
                    'token_prefix' => substr($newToken, 0, 4),
                ]);

                return;
            }

            // Validate webhook secret format
            if (! str_starts_with($newWebhookSecret, 'whsec_')) {
                Log::error('Invalid webhook secret format in rotation metadata', [
                    'secret_prefix' => substr($newWebhookSecret, 0, 6),
                ]);

                return;
            }

            // Update both values atomically
            $this->configManager->setMultiple([
                'SCALPELS_SERVICE_TOKEN' => $newToken,
                'SCALPELS_WEBHOOK_SECRET' => $newWebhookSecret,
            ]);

            Log::info('Service token rotated successfully', [
                'token_prefix' => substr($newToken, 0, 7),
                'secret_prefix' => substr($newWebhookSecret, 0, 9),
            ]);
        } catch (\Throwable $e) {
            // Log error but don't throw - allow response to continue
            Log::error('Failed to process token rotation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
