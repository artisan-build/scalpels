<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Scalpels API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for your Scalpels portal. This should be the HTTPS URL
    | provided during onboarding (e.g., https://myteam.scalpels.app).
    | Set via SCALPELS_URL environment variable.
    |
    */
    'url' => env('SCALPELS_URL'),

    /*
    |--------------------------------------------------------------------------
    | Project UUID
    |--------------------------------------------------------------------------
    |
    | Your unique project identifier within the Scalpels platform. This UUID
    | is provided during project setup and associates API requests with your
    | specific project. Set via SCALPELS_PROJECT environment variable.
    |
    */
    'project_id' => env('SCALPELS_PROJECT'),

    /*
    |--------------------------------------------------------------------------
    | Service Token
    |--------------------------------------------------------------------------
    |
    | Service token for deployed environment API access (svc_ prefix).
    | Generated via "php artisan scalpels:generate-service-token" command.
    | Only use in deployed environments - never in local development.
    | Set via SCALPELS_SERVICE_TOKEN environment variable.
    |
    */
    'service_token' => env('SCALPELS_SERVICE_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Secret key for verifying webhook signatures (whsec_ prefix).
    | Provided when generating service token. Used by VerifyWebhookSignature
    | middleware to validate incoming webhook requests from Scalpels.
    | Set via SCALPELS_WEBHOOK_SECRET environment variable.
    |
    */
    'webhook_secret' => env('SCALPELS_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Default Account Name
    |--------------------------------------------------------------------------
    |
    | The default account name for storing account tokens in ~/.scalpels/.
    | Account tokens are used for local development and CLI operations.
    | Use different account names when working with multiple Scalpels accounts.
    | Set via SCALPELS_ACCOUNT environment variable.
    |
    */
    'default_account' => env('SCALPELS_ACCOUNT', 'default'),

    /*
    |--------------------------------------------------------------------------
    | API Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds to wait for API requests to complete.
    | Increase this value if working with slow network connections or
    | large file transfers. Defaults to 30 seconds.
    | Set via SCALPELS_TIMEOUT environment variable.
    |
    */
    'timeout' => (int) env('SCALPELS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode to log detailed request and response information.
    | Useful for troubleshooting API issues but should be disabled in
    | production to avoid logging sensitive data. Defaults to false.
    | Set via SCALPELS_DEBUG environment variable.
    |
    */
    'debug' => (bool) env('SCALPELS_DEBUG', false),
];
