<?php

use Illuminate\Support\Facades\Http;
use Mason\Captcha\CaptchaManager;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\Drivers\HCaptchaDriver;
use Mason\Captcha\Drivers\ReCaptchaEnterpriseDriver;
use Mason\Captcha\Drivers\ReCaptchaV2Driver;
use Mason\Captcha\Drivers\ReCaptchaV3Driver;
use Mason\Captcha\Drivers\TurnstileDriver;
use Mason\Captcha\Facades\Captcha;
use Mason\Captcha\VerificationResult;

it('binds the manager as a singleton with an alias', function () {
    expect(app('captcha'))->toBeInstanceOf(CaptchaManager::class)
        ->and(app('captcha'))->toBe(app(CaptchaManager::class));
});

it('resolves every built-in driver', function (string $name, string $class) {
    expect(Captcha::driver($name))->toBeInstanceOf($class);
})->with([
    ['recaptcha_v2', ReCaptchaV2Driver::class],
    ['recaptcha_v3', ReCaptchaV3Driver::class],
    ['hcaptcha', HCaptchaDriver::class],
    ['turnstile', TurnstileDriver::class],
]);

it('resolves the enterprise driver, preferring a container-bound client', function () {
    // Binding a client avoids the SDK looking up real credentials.
    $this->fakeEnterpriseTransport();

    expect(Captcha::driver('recaptcha_enterprise'))->toBeInstanceOf(ReCaptchaEnterpriseDriver::class);
});

it('uses the configured default driver', function () {
    config(['captcha.default' => 'recaptcha_v2']);

    expect(Captcha::driver())->toBeInstanceOf(ReCaptchaV2Driver::class)
        ->and(Captcha::responseFieldName())->toBe('g-recaptcha-response');
});

it('delegates verify to the default driver', function () {
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    expect(Captcha::verify('the-token', '1.2.3.4')->passed())->toBeTrue();
});

it('supports custom drivers via extend', function () {
    Captcha::extend('always_pass', fn () => new class () implements CaptchaDriver {
        public function verify(string $token, ?string $ip = null): VerificationResult
        {
            return new VerificationResult(success: true);
        }

        public function responseFieldName(): string
        {
            return 'custom-response';
        }

        public function scriptUrl(): string
        {
            return 'https://example.test/captcha.js';
        }

        public function siteKey(): string
        {
            return 'custom-key';
        }
    });

    expect(Captcha::driver('always_pass')->verify('anything')->passed())->toBeTrue();
});

it('respects skip_environments', function () {
    expect(Captcha::shouldSkip())->toBeFalse();

    config(['captcha.skip_environments' => ['testing']]);

    expect(Captcha::shouldSkip())->toBeTrue();
});
