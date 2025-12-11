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
class GetRecentErrorsTool extends Tool
{
    use LoadsScalpelsConfig;

    /**
     * The tool's description.
     */
    protected string $description = 'Get recent error-level logs (error, critical, alert, emergency) from the last hour. Useful for quickly identifying active issues.';

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
            'hours' => $schema->integer()
                ->description('Number of hours to look back (default: 1)'),
            'limit' => $schema->integer()
                ->description('Number of results to return (1-1000, default: 50)'),
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

            $hours = $request->integer('hours', 1);
            $start = now()->subHours($hours)->toIso8601String();
            $end = now()->toIso8601String();

            $searchRequest = new SearchLogsRequest(
                projectId: $request->string('project_id'),
                environment: $request->get('environment'),
                level: 'error,critical,alert,emergency',
                start: $start,
                end: $end,
                limit: $request->integer('limit', 50),
            );

            $response = $connector->send($searchRequest);

            if (! $response->successful()) {
                return Response::error('Failed to retrieve recent errors: '.$response->body());
            }

            return Response::json($response->json());
        } catch (Throwable $throwable) {
            return Response::error('Failed to retrieve recent errors: '.$throwable->getMessage());
        }
    }
}
