# Scalpels Laravel Package

A Laravel-first Composer package that provides a unified CLI interface and SDK for interacting with Scalpels tools, enabling developers to authenticate with account tokens for local development, generate service tokens for deployed environments, and consume tool APIs through a consistent, well-documented interface with automatic token rotation.

## Installation

Install the package via Composer:

```bash
composer require artisan-build/scalpels
```

The package will auto-register its service provider and commands via Laravel's package discovery.

## Quick Start

### 1. Initialize Your Project

Configure your project with Scalpels URL and project ID:

```bash
php artisan scalpels:install --url=https://myteam.scalpels.app --project={uuid}
```

This command:
- Writes `SCALPELS_URL` and `SCALPELS_PROJECT` to both `.env` and `.env.example`
- Prepares your project for authentication

### 2. Authenticate

Authenticate with your personal account credentials:

```bash
php artisan scalpels:auth
```

You'll be prompted for:
- Email address
- Password
- Two-factor authentication code (if enabled)

The command stores your account token in `~/.scalpels/default.json` for reuse across projects.

### 3. Start Using Scalpels Tools

Once authenticated, you can use tool-specific commands:

```bash
# Example: Pull environment variables (Environmental tool)
php artisan environmental:pull

# Example: Upload file (ALF tool)
php artisan alf:upload path/to/file.txt
```

## Command Reference

### Core Commands

#### `scalpels:install`

Initialize Scalpels configuration in your project.

**Options:**
- `--url` (required): Scalpels API URL (e.g., `https://myteam.scalpels.app`)
- `--project` (required): Project UUID from Scalpels portal

**Example:**
```bash
php artisan scalpels:install \
    --url=https://myteam.scalpels.app \
    --project=550e8400-e29b-41d4-a716-446655440000
```

---

#### `scalpels:auth`

Authenticate with Scalpels using account credentials.

**Options:**
- `--name` (optional, default: `default`): Account name for token storage
- `--expires-in-days` (optional): Number of days until token expires

**Examples:**

Basic authentication:
```bash
php artisan scalpels:auth
```

Named account (for working with multiple clients):
```bash
php artisan scalpels:auth --name=client-two
```

With expiration:
```bash
php artisan scalpels:auth --expires-in-days=30
```

**Notes:**
- Account tokens are stored in `~/.scalpels/{name}.json`
- Tokens are reusable across all your projects
- File permissions are set to `0600` (owner read/write only)
- Two-factor authentication is required if enabled on your account

---

#### `scalpels:switch-account`

Switch between stored account tokens.

**Arguments:**
- `account` (optional): Account name to switch to (prompts if not provided)

**Examples:**

Interactive menu:
```bash
php artisan scalpels:switch-account
```

Direct switch:
```bash
php artisan scalpels:switch-account client-two
```

**Notes:**
- Lists all stored accounts if no argument provided
- Shows account expiration dates
- Updates `~/.scalpels/current` symlink

---

#### `scalpels:generate-service-token`

Generate a service token for deployed environments.

**Arguments:**
- `environment` (required): Environment name (e.g., `production`, `staging`)
- `type` (required): Environment type - one of:
  - `production` - Production environment
  - `staging` - Staging environment
  - `preview` - Preview/review environment
  - `development` - Development environment
  - `testing` - Testing/CI environment

**Options:**
- `--name` (optional): Custom name for the service token

**Example:**
```bash
php artisan scalpels:generate-service-token production production
```

With custom name:
```bash
php artisan scalpels:generate-service-token staging staging --name="Staging v2"
```

**Output:**
```
Service token created successfully!

Environment: production
Type: production
Token: svc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Webhook Secret: whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

⚠ Store these credentials securely! They will not be shown again.

Add these to your deployment environment variables:
  SCALPELS_SERVICE_TOKEN=svc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
  SCALPELS_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Notes:**
- Service tokens are NOT stored locally - you must add them to your deployment configuration
- Service tokens authenticate without user interaction
- Automatic rotation is supported (see Token Rotation section)

---

## Service Token Generation Workflow

### Step-by-Step Guide

1. **Authenticate locally** (if not already authenticated):
   ```bash
   php artisan scalpels:auth
   ```

2. **Generate service token** for your environment:
   ```bash
   php artisan scalpels:generate-service-token production production
   ```

3. **Copy the output** tokens to your clipboard

4. **Add to deployment** environment variables:
   - `SCALPELS_SERVICE_TOKEN` - The service token
   - `SCALPELS_WEBHOOK_SECRET` - The webhook secret (for webhook verification)

5. **Deploy your application**

The package SDK will automatically detect the service token and use it for API requests.

### Environment Types

Choose the appropriate type for your deployment:

- **production**: Live production environment serving customers
- **staging**: Pre-production environment for testing
- **preview**: Temporary preview environments for pull requests
- **development**: Development servers accessible by team
- **testing**: CI/CD runners and automated testing environments

### Token Priority

The SDK resolves tokens with the following priority:

1. **Service Token** (`SCALPELS_SERVICE_TOKEN` in `.env`)
2. **Account Token** (from `~/.scalpels/current` or specified account)
3. **None** (throws `AuthenticationException`)

This means service tokens always take precedence in deployed environments.

---

## Webhook Setup

### Registering Webhook Routes

Tool modules can register webhook endpoints in `routes/scalpels.php`:

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScalpelsWebhookController;
use ArtisanBuild\Scalpels\Middleware\VerifyWebhookSignature;

Route::prefix('webhooks/scalpels')
    ->middleware(['web', VerifyWebhookSignature::class])
    ->group(function () {
        Route::post('/environmental', [ScalpelsWebhookController::class, 'environmental']);
        Route::post('/alf', [ScalpelsWebhookController::class, 'alf']);
        Route::post('/glimpse', [ScalpelsWebhookController::class, 'glimpse']);
    });
```

### Webhook Signature Verification

All webhook requests are automatically verified using HMAC-SHA256 signatures:

1. Scalpels sends webhook with `X-Scalpels-Signature` header
2. Middleware computes HMAC-SHA256 hash of request body using your `SCALPELS_WEBHOOK_SECRET`
3. Middleware compares signatures using constant-time comparison
4. Request proceeds only if signatures match

**Security Notes:**
- Signatures use `hash_equals()` for constant-time comparison (prevents timing attacks)
- Invalid or missing signatures return `401 Unauthorized`
- Missing webhook secret configuration returns `500 Internal Server Error`

### Example Webhook Controller

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ScalpelsWebhookController extends Controller
{
    public function environmental(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $data = $request->input('data');

        // Process webhook event
        match ($event) {
            'secret.created' => $this->handleSecretCreated($data),
            'secret.updated' => $this->handleSecretUpdated($data),
            'secret.deleted' => $this->handleSecretDeleted($data),
            default => null,
        };

        return response()->json(['status' => 'success']);
    }

    private function handleSecretCreated(array $data): void
    {
        // Handle secret creation
    }
}
```

---

## Automatic Token Rotation

Service tokens support automatic rotation without application downtime.

### How It Works

1. **Admin triggers rotation** in Scalpels portal
2. Service account marked as `requires_rotation` in database
3. **Application makes next API request** (e.g., fetching secrets)
4. Middleware detects rotation flag and generates new credentials
5. **Response includes new credentials** in `meta` section:
   ```json
   {
     "data": { ... },
     "meta": {
       "token_rotation_required": true,
       "new_token": "svc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
       "new_webhook_secret": "whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
     }
   }
   ```
6. **SDK automatically updates** `.env` file with new credentials
7. Application continues working with new token
8. Old token revoked with reason `"rotation"`

### Zero Downtime

Token rotation is designed for zero downtime:

- Old token remains valid until response is received
- New credentials written atomically to `.env` file
- No manual intervention required
- Automatic rollback if update fails

### Manual Rotation Testing

To test rotation locally:

1. Generate initial service token
2. Trigger rotation via Scalpels portal
3. Make any API request (e.g., `php artisan environmental:pull`)
4. Check `.env` file - credentials should be updated automatically

---

## Troubleshooting

### Authentication Issues

**Error: "Scalpels not configured"**

Solution: Run the install command first:
```bash
php artisan scalpels:install --url=https://myteam.scalpels.app --project={uuid}
```

---

**Error: "Two-factor authentication is required"**

Solution: Enable 2FA in your Scalpels account settings at `{your-scalpels-url}/settings`, then re-run:
```bash
php artisan scalpels:auth
```

---

**Error: "Invalid credentials"**

Solutions:
- Double-check your email and password
- Ensure your account is active
- Try resetting your password if needed

---

### Token Issues

**Error: "No authentication token available"**

Solution: Authenticate first:
```bash
php artisan scalpels:auth
```

---

**Error: "Account token must start with acc_"**

Solution: This indicates corrupted token storage. Delete and re-authenticate:
```bash
rm ~/.scalpels/default.json
php artisan scalpels:auth
```

---

**Account tokens not persisting across projects**

Solution: Verify token file exists and has correct permissions:
```bash
ls -la ~/.scalpels/
# Should show files with permissions -rw------- (0600)
```

If permissions are wrong:
```bash
chmod 600 ~/.scalpels/*.json
```

---

### Configuration Issues

**Error: "Failed to update .env file"**

Solutions:
- Check `.env` file permissions (should be writable)
- Ensure sufficient disk space
- Verify no other process has `.env` file locked

---

**Changes to .env not taking effect**

Solutions:
- Clear Laravel config cache: `php artisan config:clear`
- Restart development server if using `php artisan serve`
- Check for cached environment in deployment systems

---

### Webhook Issues

**Webhooks returning 401 Unauthorized**

Solutions:
- Verify `SCALPELS_WEBHOOK_SECRET` is set in `.env`
- Check webhook secret matches what Scalpels has on file
- Ensure `VerifyWebhookSignature` middleware is applied to routes

---

**Webhooks returning 500 Internal Server Error**

Solution: Add webhook secret to `.env`:
```env
SCALPELS_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

---

### Service Token Issues

**Service tokens not working in deployment**

Solutions:
- Verify `SCALPELS_SERVICE_TOKEN` is set in deployment environment
- Check token starts with `svc_` prefix
- Ensure token hasn't been revoked in Scalpels portal
- Verify `SCALPELS_URL` and `SCALPELS_PROJECT` are also set

---

**Token rotation not updating .env**

Solutions:
- Check application has write permissions to `.env` file
- Verify `.env` file exists in expected location
- Check application logs for rotation errors
- Ensure `ConfigManager` is not failing silently

---

## Configuration

### Environment Variables

```env
# Scalpels API Configuration
SCALPELS_URL=https://myteam.scalpels.app
SCALPELS_PROJECT=550e8400-e29b-41d4-a716-446655440000

# Service Token (for deployed environments only)
SCALPELS_SERVICE_TOKEN=svc_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Webhook Secret (for webhook verification)
SCALPELS_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Optional: Default account name for token storage
SCALPELS_ACCOUNT=default

# Optional: Request timeout in seconds
SCALPELS_TIMEOUT=30

# Optional: Enable debug mode for verbose output
SCALPELS_DEBUG=false
```

### Published Configuration

Publish the configuration file for customization:

```bash
php artisan vendor:publish --provider="ArtisanBuild\Scalpels\Providers\ScalpelsServiceProvider"
```

This creates `config/scalpels.php`:

```php
return [
    'url' => env('SCALPELS_URL'),
    'project_id' => env('SCALPELS_PROJECT'),
    'service_token' => env('SCALPELS_SERVICE_TOKEN'),
    'webhook_secret' => env('SCALPELS_WEBHOOK_SECRET'),
    'default_account' => env('SCALPELS_ACCOUNT', 'default'),
    'timeout' => env('SCALPELS_TIMEOUT', 30),
    'debug' => env('SCALPELS_DEBUG', false),
];
```

---

## Security Considerations

### Account Token Storage

- Tokens stored in `~/.scalpels/` with `0600` permissions (owner read/write only)
- JSON files contain: `account_token_id`, `token`, `expires_at`, `created_at`
- Never commit `~/.scalpels/` to version control

### Service Token Storage

- **Never commit** `SCALPELS_SERVICE_TOKEN` to version control
- Store in deployment environment variables only
- Rotate tokens regularly via Scalpels portal
- Revoke tokens immediately if compromised

### Webhook Security

- All webhooks use HMAC-SHA256 signature verification
- Constant-time comparison prevents timing attacks
- Always use `VerifyWebhookSignature` middleware on webhook routes
- Keep `SCALPELS_WEBHOOK_SECRET` secure and rotate during token rotation

---

## Testing

The package includes comprehensive test coverage:

```bash
# Run all package tests
vendor/bin/pest packages/scalpels

# Run with coverage report (requires Xdebug or PCOV)
vendor/bin/pest packages/scalpels --coverage

# Run specific test file
vendor/bin/pest packages/scalpels/tests/Feature/AuthCommandTest.php

# Run tests matching filter
vendor/bin/pest packages/scalpels --filter=AuthCommand
```

### Test Categories

- **Unit Tests**: TokenManager, ConfigManager, TokenRotationHandler, WebhookSigner, Connector, Requests, Resources
- **Feature Tests**: Commands (Install, Auth, SwitchAccount, GenerateServiceToken), Middleware
- **Edge Cases**: Error handling, corrupted files, permission issues, special characters
- **Integration Tests**: End-to-end authentication workflows, token rotation, multi-account management

---

## Requirements

- PHP 8.4+
- Laravel 12+
- Saloon 3.0+

---

## License

Proprietary - Artisan Build LLC

---

## Support

For issues, questions, or feature requests:

- Documentation: [https://docs.scalpels.app](https://docs.scalpels.app)
- Support Email: support@scalpels.app
- Portal: Your team's Scalpels portal

---

## Credits

Developed by [Artisan Build](https://artisan.build)
