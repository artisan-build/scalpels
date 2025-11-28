<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Providers;

use ArtisanBuild\Scalpels\Commands\AuthCommand;
use ArtisanBuild\Scalpels\Commands\GenerateServiceTokenCommand;
use ArtisanBuild\Scalpels\Commands\InstallCommand;
use ArtisanBuild\Scalpels\Commands\SwitchAccountCommand;
use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenManager;
use Illuminate\Support\ServiceProvider;

/**
 * ScalpelsServiceProvider registers package configuration, services, and commands.
 *
 * This provider handles:
 * - Configuration file merging and publishing
 * - Singleton binding for TokenManager and ConfigManager
 * - Command registration for CLI interface
 * - Route file loading for webhook support
 */
class ScalpelsServiceProvider extends ServiceProvider
{
    /**
     * Register package services and bindings.
     */
    public function register(): void
    {
        // Merge package configuration with application configuration
        $this->mergeConfigFrom(
            __DIR__.'/../../config/scalpels.php',
            'scalpels'
        );

        // Bind TokenManager as singleton for token resolution and storage
        $this->app->singleton(fn ($app): TokenManager => new TokenManager);

        // Bind ConfigManager as singleton for .env file operations
        $this->app->singleton(fn ($app): ConfigManager => new ConfigManager);
    }

    /**
     * Bootstrap package services and publish assets.
     */
    public function boot(): void
    {
        // Load package routes for webhook support
        $this->loadRoutesFrom(__DIR__.'/../../routes/scalpels.php');

        // Register publishable configuration file
        $this->publishes([
            __DIR__.'/../../config/scalpels.php' => config_path('scalpels.php'),
        ], 'scalpels-config');

        // Register publishable routes file
        $this->publishes([
            __DIR__.'/../../routes/scalpels.php' => base_path('routes/scalpels.php'),
        ], 'scalpels-routes');

        // Register commands when running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                AuthCommand::class,
                SwitchAccountCommand::class,
                GenerateServiceTokenCommand::class,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            TokenManager::class,
            ConfigManager::class,
        ];
    }
}
