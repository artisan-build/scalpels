<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Requests\Intercept;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Request for capturing an email in the Intercept module.
 *
 * Sends email data to the Scalpels API for storage and display.
 */
class CaptureEmailRequest extends Request implements HasBody
{
    use HasJsonBody;

    /**
     * The HTTP method for this request.
     */
    protected Method $method = Method::POST;

    /**
     * Create a new request instance.
     *
     * @param  array<int, array{email: string, name?: string}>  $from
     * @param  array<int, array{email: string, name?: string}>  $to
     * @param  array<int, array{email: string, name?: string}>|null  $cc
     * @param  array<int, array{email: string, name?: string}>|null  $bcc
     * @param  array<int, array{email: string, name?: string}>|null  $replyTo
     * @param  array<string, string>|null  $headers
     * @param  array<int, array{filename: string, content: string, content_type: string, size: int}>|null  $attachments
     */
    public function __construct(
        protected string $projectId,
        protected string $environment,
        protected array $from,
        protected array $to,
        protected string $subject,
        protected ?string $htmlBody = null,
        protected ?string $textBody = null,
        protected ?array $cc = null,
        protected ?array $bcc = null,
        protected ?array $replyTo = null,
        protected ?array $headers = null,
        protected ?array $attachments = null,
    ) {}

    /**
     * Resolve the endpoint for this request.
     */
    public function resolveEndpoint(): string
    {
        return '/api/v1/intercept/emails';
    }

    /**
     * Define the default body for this request.
     *
     * @return array<string, mixed>
     */
    public function defaultBody(): array
    {
        return array_filter([
            'project_id' => $this->projectId,
            'environment' => $this->environment,
            'from' => $this->from,
            'to' => $this->to,
            'subject' => $this->subject,
            'html_body' => $this->htmlBody,
            'text_body' => $this->textBody,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'reply_to' => $this->replyTo,
            'headers' => $this->headers,
            'attachments' => $this->attachments,
        ], fn (string|array|null $value): bool => $value !== null);
    }
}
