<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Commands;

use ArtisanBuild\Scalpels\Exceptions\AuthenticationException;
use ArtisanBuild\Scalpels\Requests\CreateAccountTokenRequest;
use ArtisanBuild\Scalpels\Resources\AccountTokenResource;
use Exception;
use Saloon\Exceptions\Request\RequestException;

/**
 * Authenticate with Scalpels using account credentials.
 *
 * Prompts for email and password, creates an account token via API,
 * and stores it in ~/.scalpels for reuse across projects.
 */
class AuthCommand extends ScalpelsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scalpels:auth
                            {--name=default : Account name for token storage}
                            {--expires-in-days= : Number of days until token expires}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Authenticate with Scalpels using account credentials';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check configuration
        if (! config('scalpels.url') || ! config('scalpels.project_id')) {
            $this->failure('Scalpels not configured. Please run: php artisan scalpels:install');

            return self::FAILURE;
        }

        $name = $this->option('name');
        $expiresInDays = $this->option('expires-in-days');

        // Prompt for credentials
        $email = $this->ask('Email');
        $password = $this->secret('Password');

        if (! $email || ! $password) {
            $this->failure('Email and password are required');

            return self::FAILURE;
        }

        try {
            // For CreateAccountTokenRequest, we need to provide two_factor_code
            // If user doesn't have 2FA, they can just press enter (empty string)
            $twoFactorCode = $this->ask('Two-factor code (press Enter if not enabled)') ?? '';

            // Create account token request
            $request = new CreateAccountTokenRequest(
                email: $email,
                password: $password,
                twoFactorCode: $twoFactorCode,
                expiresInDays: $expiresInDays ? (int) $expiresInDays : null,
            );

            // Send request (connector will handle unauthenticated requests)
            $connector = $this->scalpels();
            $response = $connector->send($request);

            // Parse response
            $accountToken = AccountTokenResource::fromResponse($response);

            // Store token
            $this->tokenManager()->storeAccountToken($name, [
                'account_token_id' => $accountToken->id,
                'token' => $accountToken->token,
                'expires_at' => $accountToken->expiresAt?->toIso8601String(),
                'created_at' => $accountToken->createdAt->toIso8601String(),
            ]);

            $this->success('Authentication successful!');
            $this->newLine();
            $this->info("Account: {$name}");

            if ($accountToken->expiresAt) {
                $this->info("Expires: {$accountToken->expiresAt->toDateString()}");
            } else {
                $this->info('Expires: Never');
            }

            $this->newLine();

            return self::SUCCESS;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $body = $response->json();

            // Handle specific error codes
            if (isset($body['code'])) {
                return match ($body['code']) {
                    'INVALID_CREDENTIALS' => $this->handleInvalidCredentials(),
                    'TWO_FACTOR_REQUIRED' => $this->handleTwoFactorRequired(),
                    default => $this->handleGenericError($body['error'] ?? 'Authentication failed'),
                };
            }

            return $this->handleGenericError($e->getMessage());
        } catch (AuthenticationException $e) {
            // This shouldn't happen for auth command, but handle it gracefully
            return $this->handleGenericError($e->getMessage());
        } catch (Exception $e) {
            return $this->handleGenericError($e->getMessage());
        }
    }

    /**
     * Handle invalid credentials error.
     */
    private function handleInvalidCredentials(): int
    {
        $this->failure('Invalid credentials. Please try again.');

        return self::FAILURE;
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
     * Handle generic error.
     */
    private function handleGenericError(string $message): int
    {
        $this->failure($message);

        return self::FAILURE;
    }
}
