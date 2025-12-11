<?php

declare(strict_types=1);

namespace Scalpels\Mcp\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class SearchLogsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $projectId,
        protected ?string $environment = null,
        protected ?string $level = null,
        protected ?string $start = null,
        protected ?string $end = null,
        protected ?string $search = null,
        protected ?string $requestId = null,
        protected int $limit = 100,
        protected int $offset = 0,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v1/scribe/logs';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'project_id' => $this->projectId,
            'environment' => $this->environment,
            'level' => $this->level,
            'start' => $this->start,
            'end' => $this->end,
            'search' => $this->search,
            'request_id' => $this->requestId,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ], fn (string|int|null $value): bool => $value !== null);
    }
}
