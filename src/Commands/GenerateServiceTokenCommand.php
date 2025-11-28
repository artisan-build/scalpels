<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Commands;

use ArtisanBuild\Scalpels\Requests\CreateServiceTokenRequest;
use ArtisanBuild\Scalpels\Resources\ServiceAccountResource;
use Exception;
use Saloon\Exceptions\Request\RequestException;

/**
 * Generate a service token for deployed environments.
 *
 * Creates a service token via API and displays the token and webhook
 * secret for adding to deployment environment variables.
 */
class GenerateServiceTokenCommand extends ScalpelsCommand
{
    /**
     * Valid environment types.
     *
     * @var array<string>
     */
    private const VALID_TYPES = ['production', 'staging', 'preview', 'development', 'testing'];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scalpels:generate-service-token
                            {environment : Environment name (e.g., production, staging)}
                            {type : Environment type (production, staging, preview, development, testing)}
                            {--name= : Optional name for the service token}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a service token for deployed environments';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $environment = $this->argument('environment');
        $type = $this->argument('type');
        $name = $this->option('name');
        $projectId = config('scalpels.project_id');

        // Validate type
        if (! in_array($type, self::VALID_TYPES, true)) {
            $this->failure('Environment type must be one of: '.implode(', ', self::VALID_TYPES));

            return self::FAILURE;
        }

        // Validate project ID
        if (! $projectId) {
            $this->failure('Project ID not configured. Please run: php artisan scalpels:install');

            return self::FAILURE;
        }

        try {
            // Create service token request
            $request = new CreateServiceTokenRequest(
                projectId: $projectId,
                environment: $environment,
                environmentType: $type,
                name: $name,
            );

            // Send request
            $connector = $this->scalpels();
            $response = $connector->send($request);

            // Parse response
            $serviceAccount = ServiceAccountResource::fromResponse($response);

            // Display success
            $this->success('Service token created successfully!');
            $this->newLine();

            if ($name) {
                $this->info("Name: {$name}");
            }

            $this->info("Environment: {$environment}");
            $this->info("Type: {$type}");
            $this->newLine();

            // Display credentials
            $this->warning('Store these credentials securely in your deployment environment:');
            $this->newLine();

            $this->line('<fg=cyan>Service Token:</>');
            $this->line("  {$serviceAccount->serviceToken}");
            $this->newLine();

            $this->line('<fg=cyan>Webhook Secret:</>');
            $this->line("  {$serviceAccount->webhookSecret}");
            $this->newLine();

            // Display environment variable names
            $this->info('Add these to your deployment environment variables:');
            $this->line('  <fg=yellow>SCALPELS_SERVICE_TOKEN</>');
            $this->line('  <fg=yellow>SCALPELS_WEBHOOK_SECRET</>');
            $this->newLine();

            return self::SUCCESS;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $body = $response->json();

            // Handle specific error codes
            if (isset($body['code'])) {
                return match ($body['code']) {
                    'TWO_FACTOR_REQUIRED' => $this->handleTwoFactorRequired(),
                    'INVALID_OPERATION' => $this->handleInvalidOperation(),
                    default => $this->handleGenericError($body['error'] ?? 'Failed to create service token'),
                };
            }

            return $this->handleGenericError($e->getMessage());
        } catch (Exception $e) {
            return $this->handleGenericError($e->getMessage());
        }
    }

    /**
     * Handle two-factor authentication required error.
     */
    private function handleTwoFactorRequired(): int
    {
        $this->failure('Two-factor authentication is required');
        $this->newLine();
        $this->info('Please enable 2FA in your account settings:');
        $this->line('  <fg=cyan>'.config('scalpels.url').'/settings</>');
        $this->newLine();

        return self::FAILURE;
    }

    /**
     * Handle invalid operation error.
     */
    private function handleInvalidOperation(): int
    {
        $this->failure('Service tokens cannot create other service tokens. Please use an account token.');

        return self::FAILURE;
    }

    /**
     * Handle generic error.
     */
    private function handleGenericError(string $message): int
    {
        $this->failure($message);

        return self::FAILURE;
    }
}
