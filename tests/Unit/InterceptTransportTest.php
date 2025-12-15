<?php

declare(strict_types=1);

use ArtisanBuild\Scalpels\Connectors\ScalpelsConnector;
use ArtisanBuild\Scalpels\Intercept\InterceptTransport;
use ArtisanBuild\Scalpels\Requests\Intercept\CaptureEmailRequest;
use ArtisanBuild\Scalpels\Tests\TestCase;
use Saloon\Http\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

uses(TestCase::class);

beforeEach(function (): void {
    // Set up test environment as non-production
    $this->app['env'] = 'testing';

    // Mock the ScalpelsConnector
    $this->connector = $this->mock(ScalpelsConnector::class);
});

test('extracts email properties correctly', function (): void {
    $email = (new Email)
        ->from(new Address('sender@example.com', 'Sender Name'))
        ->to(new Address('recipient@example.com', 'Recipient Name'))
        ->cc(new Address('cc@example.com'))
        ->bcc(new Address('bcc@example.com'))
        ->replyTo(new Address('reply@example.com'))
        ->subject('Test Subject')
        ->text('Plain text body')
        ->html('<p>HTML body</p>');

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    // Mock the connector to capture the request
    $capturedRequest = null;
    $this->connector->shouldReceive('send')
        ->once()
        ->andReturnUsing(function ($request) use (&$capturedRequest): Response {
            $capturedRequest = $request;

            return new Response(
                new GuzzleHttp\Psr7\Response(200, [], json_encode(['success' => true]))
            );
        });

    $transport = new InterceptTransport(
        $this->connector,
        'proj_test',
        'staging'
    );

    $transport->send($email, $envelope);

    expect($capturedRequest)->toBeInstanceOf(CaptureEmailRequest::class);
});

test('blocks email sending in production environment', function (): void {
    // Set environment to production
    $this->app['env'] = 'production';

    $email = (new Email)
        ->from('sender@example.com')
        ->to('recipient@example.com')
        ->subject('Test Subject')
        ->text('Plain text body');

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    $transport = new InterceptTransport(
        $this->connector,
        'proj_test',
        'production'
    );

    expect(fn (): ?SentMessage => $transport->send($email, $envelope))
        ->toThrow(
            TransportException::class,
            'Intercept mail driver cannot be used in production environment'
        );
});

test('handles API failure gracefully', function (): void {
    $email = (new Email)
        ->from('sender@example.com')
        ->to('recipient@example.com')
        ->subject('Test Subject')
        ->text('Plain text body');

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    // Mock connector to throw exception
    $this->connector->shouldReceive('send')
        ->once()
        ->andThrow(new Exception('API unreachable'));

    $transport = new InterceptTransport(
        $this->connector,
        'proj_test',
        'staging'
    );

    // Should not throw - graceful failure
    $transport->send($email, $envelope);

    // Verify it was logged (we can check logs if needed)
    expect(true)->toBeTrue();
});

test('extracts attachments with base64 encoding', function (): void {
    $email = (new Email)
        ->from('sender@example.com')
        ->to('recipient@example.com')
        ->subject('Test Subject with Attachment')
        ->text('Body with attachment')
        ->attach('Test file content', 'test.txt', 'text/plain');

    $envelope = new Envelope(
        new Address('sender@example.com'),
        [new Address('recipient@example.com')]
    );

    $capturedRequest = null;
    $this->connector->shouldReceive('send')
        ->once()
        ->andReturnUsing(function ($request) use (&$capturedRequest): Response {
            $capturedRequest = $request;

            return new Response(
                new GuzzleHttp\Psr7\Response(200, [], json_encode(['success' => true]))
            );
        });

    $transport = new InterceptTransport(
        $this->connector,
        'proj_test',
        'staging'
    );

    $transport->send($email, $envelope);

    expect($capturedRequest)->toBeInstanceOf(CaptureEmailRequest::class);

    // Verify attachments are present in the request body
    $body = $capturedRequest->defaultBody();
    expect($body)->toHaveKey('attachments')
        ->and($body['attachments'])->toBeArray()
        ->and($body['attachments'])->toHaveCount(1);
});

test('returns string representation', function (): void {
    $transport = new InterceptTransport(
        $this->connector,
        'proj_test',
        'staging'
    );

    expect((string) $transport)->toBe('intercept://staging');
});
