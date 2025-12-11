<?php

declare(strict_types=1);

namespace Scalpels\Mcp;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class ScalpelsConnector extends Connector
{
    use AcceptsJson;

    public function __construct(
        protected string $baseUrl,
        protected string $token,
    ) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
