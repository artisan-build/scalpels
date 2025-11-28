<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Commands;

use Exception;
use Ramsey\Uuid\Uuid;

/**
 * Install and configure Scalpels package.
 *
 * Validates and writes SCALPELS_URL and SCALPELS_PROJECT to both
 * .env and .env.example files, then displays next steps.
 */
class InstallCommand extends ScalpelsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scalpels:install
                            {--url= : Scalpels API URL (must be HTTPS)}
                            {--project= : Project UUID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure Scalpels package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = $this->option('url');
        $project = $this->option('project');

        // Validate URL
        if (! $url) {
            $this->failure('URL is required. Use --url option.');

            return self::FAILURE;
        }

        if (! str_starts_with($url, 'https://')) {
            $this->failure('URL must use HTTPS protocol');

            return self::FAILURE;
        }

        // Validate project UUID
        if (! $project) {
            $this->failure('Project is required. Use --project option.');

            return self::FAILURE;
        }

        if (! Uuid::isValid($project)) {
            $this->failure('Project must be a valid UUID');

            return self::FAILURE;
        }

        // Write configuration
        try {
            $configManager = $this->configManager();
            $configManager->setMultiple([
                'SCALPELS_URL' => $url,
                'SCALPELS_PROJECT' => $project,
            ]);

            $this->success('Scalpels installed successfully!');
            $this->newLine();

            // Display next steps
            $this->info('Next steps:');
            $this->line('  1. Authenticate with your account:');
            $this->line('     <fg=cyan>php artisan scalpels:auth</>');
            $this->newLine();
            $this->line('  2. Start using Scalpels tools:');
            $this->line('     <fg=cyan>php artisan environmental:pull</>');
            $this->newLine();

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->failure("Failed to write configuration: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
