<?php

namespace Mason\Captcha\Contracts;

use Mason\Captcha\VerificationResult;

interface CaptchaDriver
{
    /**
     * Verify a captcha token against the provider's siteverify endpoint.
     */
    public function verify(string $token, ?string $ip = null): VerificationResult;

    /**
     * The request field name the provider's widget stores the token in,
     * e.g. "g-recaptcha-response".
     */
    public function responseFieldName(): string;

    /**
     * The JavaScript URL the frontend must load for this provider.
     */
    public function scriptUrl(): string;

    /**
     * The public site key for this driver.
     */
    public function siteKey(): string;
}
