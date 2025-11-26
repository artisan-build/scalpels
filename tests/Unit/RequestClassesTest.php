<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Tests\Unit;

use ArtisanBuild\Scalpels\Requests\CreateAccountTokenRequest;
use ArtisanBuild\Scalpels\Requests\CreateServiceTokenRequest;
use ArtisanBuild\Scalpels\Requests\RevokeAccountTokenRequest;
use ArtisanBuild\Scalpels\Requests\RotateAccountTokenRequest;
use Saloon\Enums\Method;

/**
 * Test Request classes functionality.
 *
 * Covers endpoint resolution and body formatting.
 */
test('CreateAccountTokenRequest resolves correct endpoint', function () {
    $request = new CreateAccountTokenRequest('test@example.com', 'password', '123456');

    expect($request->resolveEndpoint())->toBe('/api/v1/auth/account-tokens')
        ->and($request->getMethod())->toBe(Method::POST);
});

test('CreateAccountTokenRequest formats body correctly', function () {
    $request = new CreateAccountTokenRequest('test@example.com', 'password', '123456', 365);

    $body = $request->defaultBody();

    expect($body)->toBe([
        'email' => 'test@example.com',
        'password' => 'password',
        'two_factor_code' => '123456',
        'expires_in_days' => 365,
    ]);
});

test('RotateAccountTokenRequest resolves correct endpoint', function () {
    $request = new RotateAccountTokenRequest;

    expect($request->resolveEndpoint())->toBe('/api/v1/auth/account-tokens/rotate')
        ->and($request->getMethod())->toBe(Method::POST);
});

test('RevokeAccountTokenRequest resolves correct endpoint with ID', function () {
    $request = new RevokeAccountTokenRequest(123);

    expect($request->resolveEndpoint())->toBe('/api/v1/auth/account-tokens/123/revoke')
        ->and($request->getMethod())->toBe(Method::POST);
});

test('CreateServiceTokenRequest resolves correct endpoint', function () {
    $request = new CreateServiceTokenRequest(
        'proj_abc123',
        'production',
        'production',
        'Production Server'
    );

    expect($request->resolveEndpoint())->toBe('/api/v1/auth/service-tokens')
        ->and($request->getMethod())->toBe(Method::POST);
});

test('CreateServiceTokenRequest formats body correctly', function () {
    $request = new CreateServiceTokenRequest(
        'proj_abc123',
        'production',
        'production',
        'Production Server'
    );

    $body = $request->defaultBody();

    expect($body)->toBe([
        'project_id' => 'proj_abc123',
        'environment' => 'production',
        'environment_type' => 'production',
        'name' => 'Production Server',
    ]);
});
