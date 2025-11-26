<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Commands;

use Carbon\Carbon;

/**
 * Switch between stored Scalpels accounts.
 *
 * Lists available accounts and allows switching via argument or
 * interactive menu. Updates the ~/.scalpels/current symlink.
 */
class SwitchAccountCommand extends ScalpelsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scalpels:switch-account {account? : Account name to switch to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch between stored Scalpels accounts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tokenManager = $this->tokenManager();

        // Get all accounts
        $accounts = $tokenManager->listAccounts();

        if (empty($accounts)) {
            $this->failure('No accounts found. Please run: php artisan scalpels:auth');

            return self::FAILURE;
        }

        // Get account from argument or prompt
        $account = $this->argument('account');

        if (! $account) {
            // Display interactive menu
            $account = $this->choice('Select account', $accounts);
        }

        // Validate account exists
        if (! in_array($account, $accounts, true)) {
            $this->failure("Account \"{$account}\" does not exist");
            $this->newLine();
            $this->info('Available accounts:');

            foreach ($accounts as $availableAccount) {
                $this->line("  - {$availableAccount}");
            }

            $this->newLine();

            return self::FAILURE;
        }

        try {
            // Switch account
            $tokenManager->switchAccount($account);

            $this->success("Switched to account: {$account}");

            // Display token info
            $tokenData = $tokenManager->getAccountToken($account);
            if ($tokenData && isset($tokenData['expires_at'])) {
                $expiresAt = Carbon::parse($tokenData['expires_at']);
                $this->info("Expires: {$expiresAt->toDateString()}");
            }

            $this->newLine();

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->failure("Failed to switch account: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
