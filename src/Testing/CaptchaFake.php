<?php

namespace Mason\Captcha\Testing;

use Closure;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\VerificationResult;
use PHPUnit\Framework\Assert;

class CaptchaFake implements CaptchaDriver
{
    /** @var array<int, array{token: string, ip: ?string}> */
    protected array $verifications = [];

    protected bool $shouldPass = true;

    protected ?float $score = null;

    public function __construct(protected float $threshold = 0.5) {}

    /**
     * All subsequent verifications fail.
     */
    public function failing(): static
    {
        $this->shouldPass = false;

        return $this;
    }

    /**
     * All subsequent verifications succeed with the given score; passing
     * depends on the configured recaptcha_v3 threshold.
     */
    public function scoring(float $score): static
    {
        $this->shouldPass = true;
        $this->score = $score;

        return $this;
    }

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $this->verifications[] = ['token' => $token, 'ip' => $ip];

        return new VerificationResult(
            success: $this->shouldPass,
            score: $this->score,
            errorCodes: $this->shouldPass ? [] : ['fake-failure'],
            threshold: $this->score !== null ? $this->threshold : null,
        );
    }

    /**
     * Mirrors the manager API so callers can keep using driver(...).
     */
    public function driver(?string $driver = null): static
    {
        return $this;
    }

    public function shouldSkip(): bool
    {
        return false;
    }

    public function expectAction(?string $action): static
    {
        return $this;
    }

    public function responseFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    public function scriptUrl(): string
    {
        return 'about:blank';
    }

    public function siteKey(): string
    {
        return 'fake-site-key';
    }

    /**
     * @param  ?Closure(string $token, ?string $ip): bool  $callback
     */
    public function assertVerified(?Closure $callback = null): void
    {
        Assert::assertNotEmpty($this->verifications, 'Expected at least one captcha verification, none happened.');

        if ($callback !== null) {
            Assert::assertTrue(
                collect($this->verifications)->contains(fn ($v) => $callback($v['token'], $v['ip'])),
                'No captcha verification matched the given callback.',
            );
        }
    }

    public function assertVerifiedTimes(int $times): void
    {
        Assert::assertCount($times, $this->verifications, sprintf(
            'Expected %d captcha verification(s), got %d.', $times, count($this->verifications),
        ));
    }

    public function assertNothingVerified(): void
    {
        Assert::assertEmpty($this->verifications, 'Expected no captcha verifications, but some happened.');
    }
}
