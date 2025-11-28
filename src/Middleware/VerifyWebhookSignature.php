<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Middleware;

use ArtisanBuild\Scalpels\Support\WebhookSigner;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Middleware to verify webhook signatures from Scalpels.
 *
 * This middleware validates that incoming webhook requests are authentic by
 * verifying the HMAC-SHA256 signature in the X-Scalpels-Signature header
 * using the webhook secret from configuration.
 */
class VerifyWebhookSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (\Illuminate\Http\Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if webhook secret is configured
        $webhookSecret = config('scalpels.webhook_secret');

        if (! $webhookSecret) {
            return new JsonResponse([
                'error' => 'Webhook secret not configured',
                'code' => 'WEBHOOK_SECRET_MISSING',
            ], 500);
        }

        // Extract signature from header
        $signature = $request->header('X-Scalpels-Signature');

        if (! $signature) {
            return new JsonResponse([
                'error' => 'Missing webhook signature',
                'code' => 'SIGNATURE_MISSING',
            ], 401);
        }

        // Get raw request payload
        $payload = $request->getContent();

        // Verify signature using WebhookSigner
        $signer = new WebhookSigner;

        if (! $signer->verify($payload, $signature, $webhookSecret)) {
            return new JsonResponse([
                'error' => 'Invalid webhook signature',
                'code' => 'SIGNATURE_INVALID',
            ], 401);
        }

        // Signature valid, allow request to proceed
        return $next($request);
    }
}
