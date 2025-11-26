<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Middleware\VerifyWebhookSignature;
use ArtisanBuild\Scalpels\Support\WebhookSigner;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

test('valid signature allows request to proceed', function () {
    // Arrange
    $payload = json_encode(['event' => 'test', 'data' => ['foo' => 'bar']]);
    $secret = 'whsec_test_secret_key';
    $signer = new WebhookSigner;
    $signature = $signer->sign($payload, $secret);

    config()->set('scalpels.webhook_secret', $secret);

    $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Scalpels-Signature', $signature);

    $middleware = new VerifyWebhookSignature;

    // Act
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('Success');
});

test('missing signature rejects request with 401', function () {
    // Arrange
    $payload = json_encode(['event' => 'test']);
    config()->set('scalpels.webhook_secret', 'whsec_test_secret');

    $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
    // No X-Scalpels-Signature header

    $middleware = new VerifyWebhookSignature;

    // Act
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(401);
    expect(json_decode($response->getContent(), true))
        ->toHaveKey('error', 'Missing webhook signature');
});

test('invalid signature rejects request with 401', function () {
    // Arrange
    $payload = json_encode(['event' => 'test']);
    $secret = 'whsec_test_secret';
    config()->set('scalpels.webhook_secret', $secret);

    $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Scalpels-Signature', 'invalid_signature_here');

    $middleware = new VerifyWebhookSignature;

    // Act
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(401);
    expect(json_decode($response->getContent(), true))
        ->toHaveKey('error', 'Invalid webhook signature');
});

test('modified payload rejects request with 401', function () {
    // Arrange
    $originalPayload = json_encode(['event' => 'test', 'amount' => 100]);
    $modifiedPayload = json_encode(['event' => 'test', 'amount' => 999]);
    $secret = 'whsec_test_secret';

    $signer = new WebhookSigner;
    $signature = $signer->sign($originalPayload, $secret);

    config()->set('scalpels.webhook_secret', $secret);

    $request = Request::create('/webhook', 'POST', [], [], [], [], $modifiedPayload);
    $request->headers->set('X-Scalpels-Signature', $signature);

    $middleware = new VerifyWebhookSignature;

    // Act
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(401);
    expect(json_decode($response->getContent(), true))
        ->toHaveKey('error', 'Invalid webhook signature');
});

test('missing webhook secret configuration returns 500', function () {
    // Arrange
    config()->set('scalpels.webhook_secret', null);

    $payload = json_encode(['event' => 'test']);
    $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
    $request->headers->set('X-Scalpels-Signature', 'some_signature');

    $middleware = new VerifyWebhookSignature;

    // Act
    $response = $middleware->handle($request, function ($req) {
        return new Response('Success', 200);
    });

    // Assert
    expect($response->getStatusCode())->toBe(500);
    expect(json_decode($response->getContent(), true))
        ->toHaveKey('error', 'Webhook secret not configured');
});
