<?php

declare(strict_types=1);

namespace ArtisanBuild\Scalpels\Intercept;

use App\Actions\IsProductionEnvironment;
use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Requests\Intercept\CaptureEmailRequest;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\RawMessage;

/**
 * Symfony mail transport for capturing emails via the Intercept module.
 *
 * This transport sends emails to the Scalpels API for storage and display
 * rather than actually delivering them.
 */
class InterceptTransport implements TransportInterface
{
    public function __construct(
        protected ScalpelsConnector $connector,
        protected string $projectId,
        protected string $environment,
    ) {}

    /**
     * Send the given email.
     *
     * @throws TransportException
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        // Block production environment
        if ($this->isProduction()) {
            throw new TransportException('Intercept mail driver cannot be used in production environment');
        }

        // Convert message to Email if needed
        if (! $message instanceof Email) {
            throw new TransportException('InterceptTransport only supports Email messages');
        }

        try {
            // Extract email properties
            $emailData = $this->extractEmailData($message);

            // Send via Saloon
            $request = new CaptureEmailRequest(
                projectId: $this->projectId,
                environment: $this->environment,
                from: $emailData['from'],
                to: $emailData['to'],
                subject: $emailData['subject'],
                htmlBody: $emailData['html_body'],
                textBody: $emailData['text_body'],
                cc: $emailData['cc'],
                bcc: $emailData['bcc'],
                replyTo: $emailData['reply_to'],
                headers: $emailData['headers'],
                attachments: $emailData['attachments'],
            );

            $this->connector->send($request);

            return new SentMessage($message, (string) $envelope);
        } catch (Exception $e) {
            // Graceful failure - log but don't throw
            Log::error('Intercept mail capture failed', [
                'error' => $e->getMessage(),
                'project_id' => $this->projectId,
                'environment' => $this->environment,
            ]);

            return new SentMessage($message, (string) $envelope);
        }
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return sprintf('intercept://%s', $this->environment);
    }

    /**
     * Extract email data from the Symfony Email object.
     *
     * @return array<string, mixed>
     */
    protected function extractEmailData(Email $email): array
    {
        return [
            'from' => $this->extractAddresses($email->getFrom()),
            'to' => $this->extractAddresses($email->getTo()),
            'cc' => $this->extractAddresses($email->getCc()),
            'bcc' => $this->extractAddresses($email->getBcc()),
            'reply_to' => $this->extractAddresses($email->getReplyTo()),
            'subject' => $email->getSubject() ?? '',
            'html_body' => $email->getHtmlBody(),
            'text_body' => $email->getTextBody(),
            'headers' => $this->extractHeaders($email),
            'attachments' => $this->extractAttachments($email),
        ];
    }

    /**
     * Extract addresses from Symfony Address objects.
     *
     * @param  array<int, Address>  $addresses
     * @return array<int, array{email: string, name?: string}>|null
     */
    protected function extractAddresses(array $addresses): ?array
    {
        if ($addresses === []) {
            return null;
        }

        return array_map(
            fn (Address $address): array => array_filter([
                'email' => $address->getAddress(),
                'name' => $address->getName(),
            ], fn (string $value): bool => $value !== null && $value !== ''),
            $addresses
        );
    }

    /**
     * Extract custom headers from the email.
     *
     * @return array<string, string>|null
     */
    protected function extractHeaders(Email $email): ?array
    {
        $headers = [];
        $headersObject = $email->getHeaders();

        foreach ($headersObject->all() as $header) {
            $name = $header->getName();

            // Skip standard headers that are already captured
            if (in_array(strtolower((string) $name), ['from', 'to', 'cc', 'bcc', 'reply-to', 'subject', 'content-type', 'mime-version'])) {
                continue;
            }

            $headers[$name] = $header->getBodyAsString();
        }

        return $headers === [] ? null : $headers;
    }

    /**
     * Extract and encode attachments.
     *
     * @return array<int, array{filename: string, content: string, content_type: string, size: int}>|null
     */
    protected function extractAttachments(Email $email): ?array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            if ($attachment instanceof DataPart) {
                $body = $attachment->getBody();
                $encoded = base64_encode($body);

                $attachments[] = [
                    'filename' => $attachment->getFilename() ?? 'attachment',
                    'content' => $encoded,
                    'content_type' => $attachment->getMediaType().'/'.$attachment->getMediaSubtype(),
                    'size' => strlen($body),
                ];
            }
        }

        return $attachments === [] ? null : $attachments;
    }

    /**
     * Check if the current environment is production.
     */
    protected function isProduction(): bool
    {
        return app(IsProductionEnvironment::class)->handle();
    }
}
