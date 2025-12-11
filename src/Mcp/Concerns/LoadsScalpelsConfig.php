<?php

declare(strict_types=1);

namespace Scalpels\Mcp\Concerns;

use Illuminate\Support\Facades\Request;
use RuntimeException;

trait LoadsScalpelsConfig
{
    protected string $scalpelsToken;

    protected string $scalpelsUrl;

    /**
     * Load Scalpels configuration from ~/.scalpels file.
     *
     * @throws RuntimeException If config file is missing or token not found
     */
    protected function loadScalpelsConfig(): void
    {
        $configPath = Request::server('HOME').'/.scalpels';

        if (! file_exists($configPath)) {
            throw new RuntimeException(
                'Scalpels configuration file not found at ~/.scalpels. '.
                'Run "scalpels login" to authenticate.'
            );
        }

        $config = json_decode(file_get_contents($configPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Invalid JSON in ~/.scalpels configuration file: '.json_last_error_msg()
            );
        }

        if (! isset($config['token']) || empty($config['token'])) {
            throw new RuntimeException(
                'No authentication token found in ~/.scalpels. '.
                'Run "scalpels login" to authenticate.'
            );
        }

        if (! isset($config['url']) || empty($config['url'])) {
            throw new RuntimeException(
                'No API URL found in ~/.scalpels configuration file.'
            );
        }

        $this->scalpelsToken = $config['token'];
        $this->scalpelsUrl = rtrim((string) $config['url'], '/');
    }

    /**
     * Get the Scalpels API token.
     */
    protected function getScalpelsToken(): string
    {
        if (! isset($this->scalpelsToken)) {
            $this->loadScalpelsConfig();
        }

        return $this->scalpelsToken;
    }

    /**
     * Get the Scalpels API URL.
     */
    protected function getScalpelsUrl(): string
    {
        if (! isset($this->scalpelsUrl)) {
            $this->loadScalpelsConfig();
        }

        return $this->scalpelsUrl;
    }
}
