<?php

namespace Mason\Captcha;

use Carbon\CarbonImmutable;

final readonly class VerificationResult
{
    /**
     * @param  ?float  $score  Only present for score-based providers (reCAPTCHA v3).
     * @param  array<int, string>  $errorCodes  Provider error codes, kept verbatim.
     * @param  array<string, mixed>  $raw  The raw siteverify response body.
     * @param  ?float  $threshold  Minimum score required for passed(); null disables the check.
     */
    public function __construct(
        public bool $success,
        public ?float $score = null,
        public ?string $action = null,
        public ?string $hostname = null,
        public ?CarbonImmutable $challengedAt = null,
        public array $errorCodes = [],
        public array $raw = [],
        private ?float $threshold = null,
    ) {}

    /**
     * Whether the request should be let through. Unlike $success, this also
     * applies the score threshold for score-based providers.
     */
    public function passed(): bool
    {
        if (! $this->success) {
            return false;
        }

        if ($this->threshold !== null && $this->score !== null) {
            return $this->score >= $this->threshold;
        }

        return true;
    }

    public function failed(): bool
    {
        return ! $this->passed();
    }
}
