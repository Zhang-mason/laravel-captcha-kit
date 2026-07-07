<?php

namespace Mason\Captcha;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Manager;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\Drivers\HCaptchaDriver;
use Mason\Captcha\Drivers\ReCaptchaV2Driver;
use Mason\Captcha\Drivers\ReCaptchaV3Driver;
use Mason\Captcha\Drivers\TurnstileDriver;

class CaptchaManager extends Manager implements CaptchaDriver
{
    public function getDefaultDriver(): string
    {
        return (string) $this->config->get('captcha.default', 'recaptcha_v2');
    }

    /**
     * Whether the rule and middleware should skip verification in the
     * current environment (see captcha.skip_environments).
     */
    public function shouldSkip(): bool
    {
        return in_array(
            $this->config->get('app.env'),
            (array) $this->config->get('captcha.skip_environments', []),
            true,
        );
    }

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        return $this->driver()->verify($token, $ip);
    }

    public function responseFieldName(): string
    {
        return $this->driver()->responseFieldName();
    }

    public function scriptUrl(): string
    {
        return $this->driver()->scriptUrl();
    }

    public function siteKey(): string
    {
        return $this->driver()->siteKey();
    }

    protected function createRecaptchaV2Driver(): CaptchaDriver
    {
        return new ReCaptchaV2Driver(...$this->driverDependencies('recaptcha_v2'));
    }

    protected function createRecaptchaV3Driver(): CaptchaDriver
    {
        return new ReCaptchaV3Driver(...$this->driverDependencies('recaptcha_v3'));
    }

    protected function createHcaptchaDriver(): CaptchaDriver
    {
        return new HCaptchaDriver(...$this->driverDependencies('hcaptcha'));
    }

    protected function createTurnstileDriver(): CaptchaDriver
    {
        return new TurnstileDriver(...$this->driverDependencies('turnstile'));
    }

    /**
     * @return array{0: HttpFactory, 1: array<string, mixed>, 2: int, 3: int, 4: string}
     */
    protected function driverDependencies(string $name): array
    {
        return [
            $this->container->make(HttpFactory::class),
            (array) $this->config->get("captcha.drivers.{$name}", []),
            (int) $this->config->get('captcha.http.timeout', 5),
            (int) $this->config->get('captcha.http.retry', 1),
            (string) $this->config->get('captcha.on_failure', 'fail'),
        ];
    }
}
