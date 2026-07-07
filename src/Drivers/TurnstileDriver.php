<?php

namespace Mason\Captcha\Drivers;

use Illuminate\Support\Str;
use Mason\Captcha\VerificationResult;

class TurnstileDriver extends AbstractDriver
{
    public function responseFieldName(): string
    {
        return 'cf-turnstile-response';
    }

    public function scriptUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    }

    protected function endpoint(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }

    protected function payload(string $token, ?string $ip): array
    {
        // Idempotency key lets retried requests share one token validation.
        return [
            ...parent::payload($token, $ip),
            'idempotency_key' => (string) Str::uuid(),
        ];
    }

    protected function mapResponse(array $data): VerificationResult
    {
        return new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            action: $data['action'] ?? null,
            hostname: $data['hostname'] ?? null,
            challengedAt: $this->challengedAt($data),
            errorCodes: (array) ($data['error-codes'] ?? []),
            raw: $data,
        );
    }
}
