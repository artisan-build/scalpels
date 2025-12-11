<?php

declare(strict_types=1);

namespace Scalpels\Mcp\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetLogRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected int $logId,
        protected ?string $projectId = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/api/v1/scribe/logs/{$this->logId}";
    }

    protected function defaultQuery(): array
    {
        if ($this->projectId === null) {
            return [];
        }

        return [
            'project_id' => $this->projectId,
        ];
    }
}
