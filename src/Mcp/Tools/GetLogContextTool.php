<?php

declare(strict_types=1);

namespace Scalpels\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Scalpels\Mcp\Concerns\LoadsScalpelsConfig;
use Scalpels\Mcp\Requests\GetLogRequest;
use Scalpels\Mcp\Requests\SearchLogsRequest;
use Scalpels\Mcp\ScalpelsConnector;
use Throwable;

#[IsReadOnly]
class GetLogContextTool extends Tool
{
    use LoadsScalpelsConfig;

    /**
     * The tool's description.
     */
    protected string $description = 'Get a specific log entry by ID with optional surrounding context. If the log has a request_id, retrieves all logs from that request for full context.';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'log_id' => $schema->integer()
                ->description('The log entry ID to retrieve')
                ->required(),
            'project_id' => $schema->string()
                ->description('The project UUID (optional for additional filtering)'),
            'include_request_context' => $schema->boolean()
                ->description('If true and log has request_id, includes all logs from that request (default: true)'),
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

            // Get the specific log entry
            $getLogRequest = new GetLogRequest(
                logId: $request->integer('log_id'),
                projectId: $request->get('project_id'),
            );

            $response = $connector->send($getLogRequest);

            if (! $response->successful()) {
                return Response::error('Failed to retrieve log: '.$response->body());
            }

            $logData = $response->json();
            $log = $logData['data'] ?? null;

            if (! $log) {
                return Response::error('Log entry not found');
            }

            // Check if we should include request context
            $includeContext = $request->boolean('include_request_context', true);

            if ($includeContext && isset($log['request_id']) && $log['request_id']) {
                // Get all logs from the same request
                $contextRequest = new SearchLogsRequest(
                    projectId: $log['project_id'],
                    requestId: $log['request_id'],
                    limit: 1000,
                );

                $contextResponse = $connector->send($contextRequest);

                if ($contextResponse->successful()) {
                    $contextData = $contextResponse->json();

                    return Response::json([
                        'log' => $log,
                        'request_context' => $contextData['data'] ?? [],
                        'total_request_logs' => $contextData['meta']['total'] ?? 0,
                    ]);
                }
            }

            return Response::json(['log' => $log]);
        } catch (Throwable $throwable) {
            return Response::error('Failed to retrieve log context: '.$throwable->getMessage());
        }
    }
}
