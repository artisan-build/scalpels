<?php

declare(strict_types=1);

namespace Scalpels\Mcp;

use Laravel\Mcp\Server;
use Scalpels\Mcp\Tools\GetLogContextTool;
use Scalpels\Mcp\Tools\GetRecentErrorsTool;
use Scalpels\Mcp\Tools\SearchLogsTool;

class ScalpelsMcpServer extends Server
{
    /**
     * The MCP server's name.
     */
    protected string $name = 'Scalpels';

    /**
     * The MCP server's version.
     */
    protected string $version = '0.1.0';

    /**
     * The MCP server's instructions for the LLM.
     */
    protected string $instructions = <<<'MARKDOWN'
        # Scalpels MCP Server

        This Model Context Protocol (MCP) server provides access to the Scalpels log aggregation system (Scribe).

        ## Authentication

        Before using these tools, ensure you have authenticated with Scalpels:
        ```bash
        scalpels login
        ```

        This creates a `~/.scalpels` configuration file with your API token and URL.

        ## Available Tools

        ### search_logs
        Search and filter logs from Scribe with various filters:
        - Filter by project_id (required), environment, log level, time range
        - Text search in log messages
        - Filter by request_id to see all logs from a specific request
        - Supports pagination with limit and offset parameters

        ### get_recent_errors
        Quick access to recent error-level logs (error, critical, alert, emergency):
        - Defaults to last 1 hour
        - Configurable time window (hours parameter)
        - Automatically filters to error-level logs only
        - Useful for quickly identifying active issues

        ### get_log_context
        Retrieve a specific log entry with full context:
        - Get single log by ID
        - If log has request_id, optionally retrieve all logs from that request
        - Provides complete context for debugging

        ## Usage Examples

        ### Find recent production errors:
        ```
        Use get_recent_errors with project_id and environment="production"
        ```

        ### Search for specific error messages:
        ```
        Use search_logs with project_id, level="error,critical", and search="database connection"
        ```

        ### Get full context for a specific log:
        ```
        Use get_log_context with log_id and include_request_context=true to see all related logs
        ```

        ## Log Levels

        Available log levels (from least to most severe):
        - debug
        - info
        - notice
        - warning
        - error
        - critical
        - alert
        - emergency

        You can filter by multiple levels using comma separation: "error,critical,alert,emergency"
    MARKDOWN;

    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Server\Tool>>
     */
    protected array $tools = [
        SearchLogsTool::class,
        GetRecentErrorsTool::class,
        GetLogContextTool::class,
    ];

    /**
     * The resources registered with this MCP server.
     *
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        //
    ];

    /**
     * The prompts registered with this MCP server.
     *
     * @var array<int, class-string<Server\Prompt>>
     */
    protected array $prompts = [
        //
    ];
}
