<?php

declare(strict_types=1);

namespace Scalpels;

use Illuminate\Support\ServiceProvider;
use Laravel\Mcp\Facades\Mcp;
use Scalpels\Console\Commands\McpInstallCommand;
use Scalpels\Console\Commands\McpServeCommand;
use Scalpels\Mcp\ScalpelsMcpServer;

class ScalpelsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register MCP server
        if (class_exists(Mcp::class)) {
            Mcp::local('scalpels', ScalpelsMcpServer::class);
        }

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                McpServeCommand::class,
                McpInstallCommand::class,
            ]);
        }
    }
}
