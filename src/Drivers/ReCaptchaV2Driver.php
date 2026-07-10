<?php

namespace Mason\Captcha\Drivers;

use Mason\Captcha\VerificationResult;

class ReCaptchaV2Driver extends AbstractDriver
{
    public function responseFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    public function scriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js';
    }

    protected function endpoint(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
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
