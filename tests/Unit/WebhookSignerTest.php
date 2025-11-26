<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Support\WebhookSigner;

beforeEach(function () {
    $this->signer = new WebhookSigner;
    $this->secret = 'whsec_test123456789';
    $this->payload = json_encode(['event' => 'test', 'data' => ['id' => 123]]);
});

test('sign generates HMAC-SHA256 signature', function () {
    $signature = $this->signer->sign($this->payload, $this->secret);

    expect($signature)->toBeString()
        ->and(strlen($signature))->toBe(64); // SHA256 hex is 64 chars
});

test('verify returns true for valid signature', function () {
    $signature = $this->signer->sign($this->payload, $this->secret);

    expect($this->signer->verify($this->payload, $signature, $this->secret))->toBeTrue();
});

test('verify returns false for invalid signature', function () {
    $invalidSignature = 'invalid_signature';

    expect($this->signer->verify($this->payload, $invalidSignature, $this->secret))->toBeFalse();
});

test('verify returns false for modified payload', function () {
    $signature = $this->signer->sign($this->payload, $this->secret);
    $modifiedPayload = json_encode(['event' => 'modified', 'data' => ['id' => 456]]);

    expect($this->signer->verify($modifiedPayload, $signature, $this->secret))->toBeFalse();
});

test('verify returns false for wrong secret', function () {
    $signature = $this->signer->sign($this->payload, $this->secret);
    $wrongSecret = 'whsec_wrong123';

    expect($this->signer->verify($this->payload, $signature, $wrongSecret))->toBeFalse();
});

test('signatures are consistent for same input', function () {
    $signature1 = $this->signer->sign($this->payload, $this->secret);
    $signature2 = $this->signer->sign($this->payload, $this->secret);

    expect($signature1)->toBe($signature2);
});
