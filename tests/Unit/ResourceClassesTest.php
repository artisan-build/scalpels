<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Tests\Unit;

use ArtisanBuild\Scalpels\Resources\AccountTokenResource;
use ArtisanBuild\Scalpels\Resources\ServiceAccountResource;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response as SaloonResponse;

/**
 * Test Resource classes functionality.
 *
 * Covers response parsing into typed objects.
 */
test('AccountTokenResource parses account token creation response', function (): void {
    $responseData = [
        'data' => [
            'id' => 123,
            'token' => 'acc_test1234567890test1234567890te',
            'expires_at' => '2026-02-08T10:30:00Z',
            'created_at' => '2025-11-10T10:30:00Z',
        ],
    ];

    // Create a proper Response object using mock
    $psrResponse = new Response(200, [], json_encode($responseData));
    $psrRequest = new Request('POST', 'https://test.scalpels.app/api/v1/auth/account-tokens');
    $mockPendingRequest = Mockery::mock(PendingRequest::class);
    $response = new SaloonResponse($psrResponse, $mockPendingRequest, $psrRequest);

    $resource = AccountTokenResource::fromResponse($response);

    expect($resource->id)->toBe(123)
        ->and($resource->token)->toBe('acc_test1234567890test1234567890te')
        ->and($resource->expiresAt)->toBe('2026-02-08T10:30:00Z')
        ->and($resource->createdAt)->toBe('2025-11-10T10:30:00Z');
});

test('ServiceAccountResource parses service token creation response', function (): void {
    $responseData = [
        'data' => [
            'id' => 456,
            'service_token' => 'svc_test1234567890test1234567890te',
            'webhook_secret' => 'whsec_test1234567890test1234567890',
            'project_id' => 'proj_abc123',
            'environment' => 'production',
            'environment_type' => 'production',
            'name' => 'Production Server',
            'created_at' => '2025-11-10T10:30:00Z',
        ],
    ];

    // Create a proper Response object using mock
    $psrResponse = new Response(200, [], json_encode($responseData));
    $psrRequest = new Request('POST', 'https://test.scalpels.app/api/v1/auth/service-tokens');
    $mockPendingRequest = Mockery::mock(PendingRequest::class);
    $response = new SaloonResponse($psrResponse, $mockPendingRequest, $psrRequest);

    $resource = ServiceAccountResource::fromResponse($response);

    expect($resource->id)->toBe(456)
        ->and($resource->serviceToken)->toBe('svc_test1234567890test1234567890te')
        ->and($resource->webhookSecret)->toBe('whsec_test1234567890test1234567890')
        ->and($resource->projectId)->toBe('proj_abc123')
        ->and($resource->environment)->toBe('production')
        ->and($resource->environmentType)->toBe('production')
        ->and($resource->name)->toBe('Production Server')
        ->and($resource->createdAt)->toBe('2025-11-10T10:30:00Z');
});
