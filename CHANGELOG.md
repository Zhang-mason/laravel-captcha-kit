# Changelog

## [Unreleased]

## [1.0.0] - 2026-07-09

### Added

- `CaptchaManager` (Laravel Manager) with drivers: `recaptcha_v2`, `recaptcha_v3`, `hcaptcha`, `turnstile`.
- `Captcha` facade with `verify()`, `driver()`, `extend()`, and `fake()`.
- `VerificationResult` value object with score-threshold-aware `passed()` / `failed()`.
- `CaptchaRule` validation rule and `captcha` route middleware.
- `<x-captcha />` Blade component (reCAPTCHA v2 checkbox + invisible).
- `Captcha::fake()` testing helper with `failing()`, `scoring()`, and assertions.
- Fail-closed transport error handling with configurable fail-open.
