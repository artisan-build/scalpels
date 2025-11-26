<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Request for creating an account token.
 *
 * Requires email, password, and 2FA code.
 */
class CreateAccountTokenRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     */
    public function __construct(
        protected string $email,
        protected string $password,
        protected string $twoFactorCode,
        protected ?int $expiresInDays = null,
    ) {}

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/auth/account-tokens';
    }

    /**
     * Define the default body for this request.
     *
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'two_factor_code' => $this->twoFactorCode,
            'expires_in_days' => $this->expiresInDays,
        ];
    }
}
