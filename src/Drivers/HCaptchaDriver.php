<?php

namespace Mason\Captcha\Drivers;

use Mason\Captcha\VerificationResult;

class HCaptchaDriver extends AbstractDriver
{
    public function responseFieldName(): string
    {
        return 'h-captcha-response';
    }

    public function scriptUrl(): string
    {
        return 'https://js.hcaptcha.com/1/api.js';
    }

    protected function endpoint(): string
    {
        return 'https://api.hcaptcha.com/siteverify';
    }

    protected function payload(string $token, ?string $ip): array
    {
        // hCaptcha optionally double-checks the token was issued to our key.
        return array_filter([
            ...parent::payload($token, $ip),
            'sitekey' => $this->siteKey(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function mapResponse(array $data): VerificationResult
    {
        return new VerificationResult(
            success: (bool) ($data['success'] ?? false),
            hostname: $data['hostname'] ?? null,
            challengedAt: $this->challengedAt($data),
            errorCodes: (array) ($data['error-codes'] ?? []),
            raw: $data,
        );
    }
}
