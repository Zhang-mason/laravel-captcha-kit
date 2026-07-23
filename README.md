# zhang-mason/captcha

Unified captcha verification for Laravel. One Manager-based API in front of Google reCAPTCHA v2 (checkbox and invisible), reCAPTCHA v3, reCAPTCHA Enterprise, hCaptcha, and Cloudflare Turnstile — switch providers by changing config, not code.

> **Status**: reCAPTCHA v2 and reCAPTCHA Enterprise are fully supported (backend verification, validation rule, middleware, and the `<x-captcha />` widget). Backend verification for reCAPTCHA v3, hCaptcha, and Turnstile is implemented; their frontend widgets are coming in a later release.

## Requirements

- PHP 8.2+ (Laravel 13 requires PHP 8.3+)
- Laravel 12 / 13

## Installation

```bash
composer require zhang-mason/laravel-captcha-kit
```

Publish the config file if you need to customise it:

```bash
php artisan vendor:publish --tag=captcha-config
```

Set your keys in `.env`:

```dotenv
CAPTCHA_DRIVER=recaptcha_v2
RECAPTCHA_V2_SITE_KEY=your-site-key
RECAPTCHA_V2_SECRET_KEY=your-secret-key
RECAPTCHA_V2_MODE=checkbox # or: invisible
```

## Usage

### Frontend

Render the widget inside your form. The script tag is included automatically and deduplicated across multiple widgets.

```blade
<form method="POST" action="/contact">
    @csrf
    <x-captcha />
    <button type="submit">Send</button>
</form>
```

In `invisible` mode the widget binds to its surrounding form (or pass `form="form-id"`) and submits automatically once the challenge completes. Score-based widgets (reCAPTCHA Enterprise in `score` mode) render a hidden input and fetch a token on submit:

```blade
<form method="POST" action="/login">
    @csrf
    <x-captcha driver="recaptcha_enterprise" action="login" />
    <button type="submit">Log in</button>
</form>
```

### Validation rule

```php
use Mason\Captcha\Rules\CaptchaRule;

public function rules(): array
{
    return [
        'g-recaptcha-response' => ['required', new CaptchaRule],
        // Target a specific driver:
        'cf-turnstile-response' => ['required', CaptchaRule::driver('turnstile')],
        // reCAPTCHA v3 with an expected action:
        'g-recaptcha-response' => ['required', CaptchaRule::driver('recaptcha_v3')->action('login')],
    ];
}
```

### Middleware

```php
Route::post('/contact', ContactController::class)->middleware('captcha');
Route::post('/login', LoginController::class)->middleware('captcha:recaptcha_v3,login');
```

Failed verifications throw a `ValidationException` (HTTP 422 for JSON requests, redirect back with errors otherwise).

### Facade

```php
use Mason\Captcha\Facades\Captcha;

$result = Captcha::verify($request->input('g-recaptcha-response'), $request->ip());

if ($result->failed()) {
    abort(422);
}

// A specific driver:
$result = Captcha::driver('turnstile')->verify($token, $ip);

// reCAPTCHA v3 exposes the score:
$result = Captcha::driver('recaptcha_v3')->verify($token, $ip);
$result->score;    // e.g. 0.9
$result->passed(); // success AND score >= configured threshold
```

`verify()` returns a `VerificationResult` with `success`, `score`, `action`, `hostname`, `challengedAt`, `errorCodes`, and the `raw` provider response. Use `passed()` / `failed()` — they also apply the v3 score threshold.

### Custom drivers

```php
use Mason\Captcha\Facades\Captcha;

Captcha::extend('my_provider', fn ($app) => new MyProviderDriver(/* ... */));
```

### Testing

```php
use Mason\Captcha\Facades\Captcha;

public function test_contact_form(): void
{
    Captcha::fake(); // every verification passes, no HTTP

    $this->post('/contact', [/* ... */])->assertOk();

    Captcha::fake()->assertVerified();
}

Captcha::fake()->failing();     // every verification fails
Captcha::fake()->scoring(0.3);  // simulate a v3 score
```

You can also skip verification entirely per environment via `captcha.skip_environments` (defaults to `['testing']`).

### reCAPTCHA Enterprise

The `recaptcha_enterprise` driver verifies tokens through the Google Cloud Assessment API instead of `siteverify`. It needs the official SDK:

```bash
composer require google/cloud-recaptcha-enterprise
```

```dotenv
CAPTCHA_DRIVER=recaptcha_enterprise
RECAPTCHA_ENTERPRISE_SITE_KEY=your-site-key
RECAPTCHA_ENTERPRISE_PROJECT_ID=your-gcp-project
# Service account JSON file; omit to use Application Default Credentials.
RECAPTCHA_ENTERPRISE_CREDENTIALS=/path/to/service-account.json
RECAPTCHA_ENTERPRISE_MODE=score # or: checkbox, matching your key type
RECAPTCHA_ENTERPRISE_THRESHOLD=0.5
```

The service account needs the `roles/recaptchaenterprise.agent` role. The SDK talks REST by default (no `grpc` PHP extension required); set `RECAPTCHA_ENTERPRISE_TRANSPORT=grpc` if you have it installed. Advanced setups can bind their own `RecaptchaEnterpriseServiceClient` in the container and the driver will use it.

Score handling and `expectAction()` work the same as reCAPTCHA v3; Enterprise-specific details such as `riskAnalysis.reasons` are available on `VerificationResult::$raw`.

## Configuration notes

- **Fail-closed by default**: if the provider's siteverify endpoint is unreachable, verification fails. Set `CAPTCHA_ON_FAILURE=pass` to fail open instead.
- reCAPTCHA v2 checkbox vs. invisible is a frontend-only difference — both use the same driver; switch with `RECAPTCHA_V2_MODE`.

## Development

```bash
composer install
composer test  # pest
composer lint  # pint
```

## License

MIT
