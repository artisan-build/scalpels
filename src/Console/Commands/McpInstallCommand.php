<?php

declare(strict_types=1);

namespace Scalpels\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Request;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'scalpels:mcp-install',
    description: 'Install Scalpels MCP server in Claude Desktop configuration'
)]
class McpInstallCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $os = PHP_OS_FAMILY;
        $configPath = $this->getClaudeConfigPath($os);

        if ($configPath === null) {
            $this->components->error('Unable to determine Claude Desktop configuration path for your OS');
            $this->line('Please manually add the following to your Claude Desktop MCP settings:');
            $this->displayConfiguration();

            return static::FAILURE;
        }

        if (! file_exists($configPath)) {
            $this->components->warn("Claude Desktop configuration file not found at: {$configPath}");
            $this->components->info('Creating configuration file...');

            $configDir = dirname($configPath);
            if (! is_dir($configDir)) {
                mkdir($configDir, 0755, true);
            }

            file_put_contents($configPath, json_encode(['mcpServers' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        $config = json_decode(file_get_contents($configPath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->components->error('Invalid JSON in Claude Desktop configuration file');

            return static::FAILURE;
        }

        // Ensure mcpServers section exists
        if (! isset($config['mcpServers'])) {
            $config['mcpServers'] = [];
        }

        // Check if Scalpels is already configured
        if (isset($config['mcpServers']['scalpels']) && ! $this->confirm('Scalpels MCP server is already configured. Overwrite?')) {
            $this->components->info('Installation cancelled');

            return static::SUCCESS;
        }

        // Add Scalpels MCP server configuration
        $artisanPath = base_path('artisan');
        $phpPath = PHP_BINARY;

        $config['mcpServers']['scalpels'] = [
            'command' => $phpPath,
            'args' => [$artisanPath, 'scalpels:mcp-serve'],
            'description' => 'Scalpels log aggregation and monitoring tools',
        ];

        // Write updated configuration
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (file_put_contents($configPath, $json) === false) {
            $this->components->error('Failed to write Claude Desktop configuration file');

            return static::FAILURE;
        }

        $this->components->success('Scalpels MCP server installed successfully!');
        $this->newLine();
        $this->components->info('Configuration written to: '.$configPath);
        $this->newLine();
        $this->components->warn('Please restart Claude Desktop for changes to take effect.');

        return static::SUCCESS;
    }

    /**
     * Get the Claude Desktop configuration path for the current OS.
     */
    protected function getClaudeConfigPath(string $os): ?string
    {
        $home = Request::server('HOME') ?? Request::server('USERPROFILE') ?? null;

        if ($home === null) {
            return null;
        }

        return match ($os) {
            'Darwin' => $home.'/Library/Application Support/Claude/claude_desktop_config.json',
            'Linux' => $home.'/.config/Claude/claude_desktop_config.json',
            'Windows' => $home.'\\AppData\\Roaming\\Claude\\claude_desktop_config.json',
            default => null,
        };
    }

    /**
     * Display the configuration that should be added manually.
     */
    protected function displayConfiguration(): void
    {
        $artisanPath = base_path('artisan');
        $phpPath = PHP_BINARY;

        $config = [
            'scalpels' => [
                'command' => $phpPath,
                'args' => [$artisanPath, 'scalpels:mcp-serve'],
                'description' => 'Scalpels log aggregation and monitoring tools',
            ],
        ];

        $this->newLine();
        $this->line(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->newLine();
    }
}
