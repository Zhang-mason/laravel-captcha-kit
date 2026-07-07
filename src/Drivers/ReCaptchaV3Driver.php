<?php

namespace Mason\Captcha\Drivers;

use Mason\Captcha\VerificationResult;

class ReCaptchaV3Driver extends AbstractDriver
{
    protected ?string $expectedAction = null;

    /**
     * Expect a specific action on the next verification. Falls back to the
     * configured "action" when not set; null skips the action check.
     */
    public function expectAction(?string $action): static
    {
        $this->expectedAction = $action;

        return $this;
    }

    public function responseFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    public function scriptUrl(): string
    {
        return 'https://www.google.com/recaptcha/api.js?render='.$this->siteKey();
    }

    protected function endpoint(): string
    {
        return 'https://www.google.com/recaptcha/api/siteverify';
    }

    protected function mapResponse(array $data): VerificationResult
    {
        $expected = $this->expectedAction ?? ($this->config['action'] ?? null);
        $this->expectedAction = null;

        $success = (bool) ($data['success'] ?? false);
        $errorCodes = (array) ($data['error-codes'] ?? []);

        if ($success && $expected !== null && ($data['action'] ?? null) !== $expected) {
            $success = false;
            $errorCodes[] = 'action-mismatch';
        }

        return new VerificationResult(
            success: $success,
            score: isset($data['score']) ? (float) $data['score'] : null,
            action: $data['action'] ?? null,
            hostname: $data['hostname'] ?? null,
            challengedAt: $this->challengedAt($data),
            errorCodes: $errorCodes,
            raw: $data,
            threshold: (float) ($this->config['threshold'] ?? 0.5),
        );
    }
}
