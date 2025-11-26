<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Commands;

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Support\ConfigManager;
use ArtisanBuild\Scalpels\Support\TokenManager;
use Illuminate\Console\Command;

/**
 * Base command class for all Scalpels commands.
 *
 * Provides helper methods for common operations like accessing
 * the authenticated SDK connector, token manager, config manager,
 * and standardized output formatting.
 */
abstract class ScalpelsCommand extends Command
{
    /**
     * Get authenticated ScalpelsConnector instance.
     */
    protected function scalpels(): ScalpelsConnector
    {
        return $this->laravel->make(ScalpelsConnector::class);
    }

    /**
     * Get TokenManager instance.
     */
    protected function tokenManager(): TokenManager
    {
        return $this->laravel->make(TokenManager::class);
    }

    /**
     * Get ConfigManager instance.
     */
    protected function configManager(): ConfigManager
    {
        return $this->laravel->make(ConfigManager::class);
    }

    /**
     * Display success message with green checkmark.
     */
    protected function success(string $message): void
    {
        $this->info("✓ {$message}");
    }

    /**
     * Display failure message with red X.
     */
    protected function failure(string $message): void
    {
        $this->error("✗ {$message}");
    }

    /**
     * Display warning message with yellow warning triangle.
     */
    protected function warning(string $message): void
    {
        $this->warn("⚠ {$message}");
    }
}
