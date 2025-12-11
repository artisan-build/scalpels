<?php

declare(strict_types=1);

namespace Scalpels\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Scalpels\Mcp\Concerns\LoadsScalpelsConfig;
use Scalpels\Mcp\Requests\SearchLogsRequest;
use Scalpels\Mcp\ScalpelsConnector;
use Throwable;

#[IsReadOnly]
class SearchLogsTool extends Tool
{
    use LoadsScalpelsConfig;

    /**
     * The tool's description.
     */
    protected string $description = 'Search and filter logs from Scribe log aggregation system. Supports filtering by project, environment, level, time range, and text search.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()
                ->description('The project UUID to query logs for')
                ->required(),
            'environment' => $schema->string()
                ->description('Filter by environment (e.g., production, staging, development)'),
            'level' => $schema->string()
                ->description('Filter by log level. Comma-separated for multiple levels (e.g., "error,critical" or "debug,info,notice,warning,error,critical,alert,emergency")'),
            'start' => $schema->string()
                ->description('Start datetime for time range filter (ISO 8601 format)'),
            'end' => $schema->string()
                ->description('End datetime for time range filter (ISO 8601 format)'),
            'search' => $schema->string()
                ->description('Text search filter for log messages'),
            'request_id' => $schema->string()
                ->description('Filter by request ID to see all logs from a specific request'),
            'limit' => $schema->integer()
                ->description('Number of results to return (1-1000, default: 100)'),
            'offset' => $schema->integer()
                ->description('Offset for pagination (default: 0)'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        try {
            $this->loadScalpelsConfig();

            $connector = new ScalpelsConnector($this->getScalpelsUrl(), $this->getScalpelsToken());

            $searchRequest = new SearchLogsRequest(
                projectId: $request->string('project_id'),
                environment: $request->get('environment'),
                level: $request->get('level'),
                start: $request->get('start'),
                end: $request->get('end'),
                search: $request->get('search'),
                requestId: $request->get('request_id'),
                limit: $request->integer('limit', 100),
                offset: $request->integer('offset', 0),
            );

            $response = $connector->send($searchRequest);

            if (! $response->successful()) {
                return Response::error('Search failed: '.$response->body());
            }

            return Response::json($response->json());
        } catch (Throwable $throwable) {
            return Response::error('Search failed: '.$throwable->getMessage());
        }
    }
}
