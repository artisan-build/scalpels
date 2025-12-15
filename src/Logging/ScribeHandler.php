<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class ScribeHandler extends AbstractProcessingHandler
{
    /**
     * Buffered logs waiting to be sent.
     */
    protected array $buffer = [];

    /**
     * Maximum number of logs per batch.
     */
    protected int $batchLimit = 1000;

    /**
     * Whether termination callback is registered.
     */
    protected bool $shutdownRegistered = false;

    public function __construct(
        protected string $token,
        protected string $projectId,
        protected string $environment,
        protected string $url,
        int|string|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->registerShutdownFunction();
    }

    /**
     * Flush buffered logs to API.
     */
    public function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $logs = array_values($this->buffer);

        // Respect batch limit
        if (count($logs) > $this->batchLimit) {
            $logs = array_slice($logs, 0, $this->batchLimit);
        }

        try {
            Http::withToken($this->token)
                ->timeout(5)
                ->post("{$this->url}/api/v1/scribe/logs", [
                    'project_id' => $this->projectId,
                    'environment' => $this->environment,
                    'logs' => $logs,
                ]);
        } catch (Throwable) {
            // Silent failure - never crash the application
        }

        $this->buffer = [];
    }

    /**
     * Close the handler and flush remaining logs.
     */
    public function close(): void
    {
        $this->flush();
    }

    /**
     * Register application termination callback.
     */
    protected function registerShutdownFunction(): void
    {
        if ($this->shutdownRegistered) {
            return;
        }

        register_shutdown_function(function (): void {
            $this->close();
        });

        $this->shutdownRegistered = true;
    }

    /**
     * Process the log record.
     */
    protected function write(LogRecord $record): void
    {
        $key = $this->getDeduplicationKey($record);

        if (isset($this->buffer[$key])) {
            // Increment count for duplicate log
            $this->buffer[$key]['count']++;
        } else {
            // Add new log to buffer
            $this->buffer[$key] = [
                'level' => strtolower($record->level->getName()),
                'message' => $record->message,
                'context' => $record->context,
                'channel' => $record->channel,
                'occurred_at' => $record->datetime->format('Y-m-d H:i:s'),
                'request_id' => $record->context['request_id'] ?? null,
                'user_id' => $record->context['user_id'] ?? null,
                'server' => gethostname(),
                'count' => 1,
            ];
        }
    }

    /**
     * Get deduplication key for log record.
     */
    protected function getDeduplicationKey(LogRecord $record): string
    {
        return $record->level->getName().'::'.$record->message;
    }
}
