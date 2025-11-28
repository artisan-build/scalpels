<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Support;

use ArtisanBuild\Scalpels\Exceptions\InvalidTokenException;
use Illuminate\Support\Facades\Request;
use RuntimeException;

/**
 * TokenManager handles account token storage and resolution.
 *
 * Manages account token storage in ~/.scalpels directory with proper file permissions
 * and locking. Resolves tokens with priority: service token → account token → null.
 */
class TokenManager
{
    /**
     * Home directory path for token storage.
     */
    private readonly string $homeDir;

    /**
     * Create a new TokenManager instance.
     *
     * @param  string|null  $homeDir  Optional home directory override for testing
     */
    public function __construct(?string $homeDir = null)
    {
        $this->homeDir = $homeDir ?? (Request::server('HOME') ?? Request::server('USERPROFILE') ?? sys_get_temp_dir());
    }

    /**
     * Resolve token with priority fallback: service → account → null.
     */
    public function resolveToken(): ?string
    {
        // Priority 1: Service token from config
        $serviceToken = config('scalpels.service_token');
        if ($serviceToken) {
            return $serviceToken;
        }

        // Priority 2: Account token from current account
        $currentAccount = $this->getCurrentAccount();
        if ($currentAccount) {
            $tokenData = $this->getAccountToken($currentAccount);
            if ($tokenData && isset($tokenData['token'])) {
                return $tokenData['token'];
            }
        }

        // No token available
        return null;
    }

    /**
     * Store account token to ~/.scalpels/{name}.json with 0600 permissions.
     *
     * @param  array<string, mixed>  $tokenData  Token data containing account_token_id, token, expires_at, created_at
     *
     * @throws InvalidTokenException If token format is invalid
     */
    public function storeAccountToken(string $name, array $tokenData): void
    {
        // Validate token format
        if (! isset($tokenData['token']) || ! str_starts_with((string) $tokenData['token'], 'acc_')) {
            throw new InvalidTokenException('Account token must start with acc_');
        }

        // Ensure .scalpels directory exists
        $scalpelsDir = "{$this->homeDir}/.scalpels";
        if (! is_dir($scalpelsDir)) {
            mkdir($scalpelsDir, 0700, true);
        }

        // Write to file with locking
        $filePath = "{$scalpelsDir}/{$name}.json";
        $tempPath = "{$filePath}.tmp";

        // Open file handle for atomic write
        $handle = fopen($tempPath, 'w');
        if ($handle === false) {
            throw new RuntimeException("Failed to open file for writing: {$tempPath}");
        }

        try {
            // Acquire exclusive lock
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException("Failed to acquire lock on file: {$tempPath}");
            }

            // Write JSON data
            fwrite($handle, json_encode($tokenData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // Release lock
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        // Set permissions to 0600 before rename
        chmod($tempPath, 0600);

        // Atomic rename
        rename($tempPath, $filePath);

        // Update current symlink to point to this account if it's the first one
        $currentLink = "{$scalpelsDir}/current";
        if (! file_exists($currentLink)) {
            $this->switchAccount($name);
        }
    }

    /**
     * Read account token from ~/.scalpels/{name}.json.
     *
     * @return array<string, mixed>|null
     */
    public function getAccountToken(string $name): ?array
    {
        $filePath = "{$this->homeDir}/.scalpels/{$name}.json";

        if (! file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Get current account name from symlink.
     */
    public function getCurrentAccount(): ?string
    {
        $symlinkPath = "{$this->homeDir}/.scalpels/current";

        if (! is_link($symlinkPath)) {
            return null;
        }

        $target = readlink($symlinkPath);
        if ($target === false) {
            return null;
        }

        // Remove .json extension
        return str_replace('.json', '', $target);
    }

    /**
     * Update ~/.scalpels/current symlink to point to specified account.
     */
    public function switchAccount(string $name): void
    {
        $scalpelsDir = "{$this->homeDir}/.scalpels";
        $symlinkPath = "{$scalpelsDir}/current";
        $targetFile = "{$name}.json";

        // Verify account exists
        if (! file_exists("{$scalpelsDir}/{$targetFile}")) {
            throw new RuntimeException("Account '{$name}' does not exist");
        }

        // Remove existing symlink if present
        if (file_exists($symlinkPath) || is_link($symlinkPath)) {
            unlink($symlinkPath);
        }

        // Create new symlink
        symlink($targetFile, $symlinkPath);
    }

    /**
     * List all stored account names.
     *
     * @return array<int, string>
     */
    public function listAccounts(): array
    {
        $scalpelsDir = "{$this->homeDir}/.scalpels";

        if (! is_dir($scalpelsDir)) {
            return [];
        }

        $files = glob("{$scalpelsDir}/*.json");
        if ($files === false) {
            return [];
        }

        $accounts = [];
        foreach ($files as $file) {
            $basename = basename($file, '.json');
            if ($basename !== 'current') {
                $accounts[] = $basename;
            }
        }

        return $accounts;
    }

    /**
     * Delete account token file.
     */
    public function deleteAccount(string $name): void
    {
        $filePath = "{$this->homeDir}/.scalpels/{$name}.json";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // If this was the current account, remove symlink
        if ($this->getCurrentAccount() === $name) {
            $symlinkPath = "{$this->homeDir}/.scalpels/current";
            if (file_exists($symlinkPath) || is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }
    }
}
