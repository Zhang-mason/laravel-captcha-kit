# Changelog

## [Unreleased]

### Added

- `recaptcha_enterprise` driver verifying tokens via the Google Cloud Assessment API (`google/cloud-recaptcha-enterprise`, suggested dependency).
- `<x-captcha />` widget for reCAPTCHA Enterprise (score + checkbox modes).
- **Action auto-resolution** for score-based drivers (`recaptcha_v3`, `recaptcha_enterprise`). The middleware, validation rule, and `<x-captcha />` component now resolve the expected action through a priority chain:
  1. Explicit value (middleware param or `CaptchaRule->action()`)
  2. Route metadata key `captcha_action` — `->metadata(['captcha_action' => 'login'])` — Laravel 13.17+ only
  3. Last segment of the named route (e.g. `auth.login` → `login`)
  4. Driver `action` config key
  The frontend widget inherits the same resolved action, so setting it once on the route keeps JS and server in sync automatically.

## [1.0.0] - 2026-07-09

### Added

- `CaptchaManager` (Laravel Manager) with drivers: `recaptcha_v2`, `recaptcha_v3`, `hcaptcha`, `turnstile`.
- `Captcha` facade with `verify()`, `driver()`, `extend()`, and `fake()`.
- `VerificationResult` value object with score-threshold-aware `passed()` / `failed()`.
- `CaptchaRule` validation rule and `captcha` route middleware.
- `<x-captcha />` Blade component (reCAPTCHA v2 checkbox + invisible).
- `Captcha::fake()` testing helper with `failing()`, `scoring()`, and assertions.
- Fail-closed transport error handling with configurable fail-open.
