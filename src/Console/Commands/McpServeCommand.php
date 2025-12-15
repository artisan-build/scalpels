<?php

declare(strict_types=1);

namespace Scalpels\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Mcp\Facades\Mcp;
use Laravel\Mcp\Server\Registrar;
use Scalpels\Mcp\ScalpelsMcpServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

#[AsCommand(
    name: 'scalpels:mcp-serve',
    description: 'Start the Scalpels MCP server'
)]
class McpServeCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Starting Scalpels MCP server...');

        try {
            // Create and start the MCP server
            $server = new ScalpelsMcpServer;
            Mcp::local('scalpels', $server);

            // Start the server
            $registrar = app(Registrar::class);
            $serverInstance = $registrar->getLocalServer('scalpels');

            if ($serverInstance === null) {
                $this->components->error('Failed to start Scalpels MCP server');

                return static::FAILURE;
            }

            $serverInstance();

            return static::SUCCESS;
        } catch (Throwable $e) {
            $this->components->error("Failed to start Scalpels MCP server: {$e->getMessage()}");

            return static::FAILURE;
        }
    }
}
