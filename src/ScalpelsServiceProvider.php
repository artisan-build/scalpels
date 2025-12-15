<?php

declare(strict_types=1);

namespace Scalpels;

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Intercept\InterceptTransport;
use Illuminate\Support\Facades\Mail;
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

        // Register Intercept mail driver
        $this->registerInterceptMailDriver();
    }

    /**
     * Register the Intercept mail driver.
     */
    protected function registerInterceptMailDriver(): void
    {
        Mail::extend('intercept', function (array $config = []): InterceptTransport {
            $connector = $this->app->make(ScalpelsConnector::class);
            $projectId = config('scalpels.project_id');
            $environment = config('scalpels.environment', config('app.env'));

            return new InterceptTransport(
                connector: $connector,
                projectId: $projectId,
                environment: $environment,
            );
        });
    }
}
