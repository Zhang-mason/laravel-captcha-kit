<?php

namespace Mason\Captcha\Drivers;

use Carbon\CarbonImmutable;
use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
use Google\Cloud\RecaptchaEnterprise\V1\Event;
use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\VerificationResult;
use Throwable;

class ReCaptchaEnterpriseDriver implements CaptchaDriver
{
    protected ?string $expectedAction = null;

    /**
     * @param  array<string, mixed>  $config  The captcha.drivers.recaptcha_enterprise entry.
     */
    public function __construct(
        protected RecaptchaEnterpriseServiceClient $client,
        protected array $config,
        protected string $onFailure = 'fail',
    ) {
    }

    /**
     * Expect a specific action on the next verification. Falls back to the
     * configured "action" when not set; null skips the action check.
     */
    public function expectAction(?string $action): static
    {
        $this->expectedAction = $action;

        return $this;
    }

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        $expected = $this->expectedAction ?? ($this->config['action'] ?? null);
        $this->expectedAction = null;

        $event = (new Event())
            ->setToken($token)
            ->setSiteKey($this->siteKey());

        if ($expected !== null) {
            $event->setExpectedAction($expected);
        }

        if ($ip !== null) {
            $event->setUserIpAddress($ip);
        }

        $request = (new CreateAssessmentRequest())
            ->setParent('projects/' . ($this->config['project_id'] ?? ''))
            ->setAssessment((new Assessment())->setEvent($event));

        try {
            $assessment = $this->client->createAssessment($request);
        } catch (Throwable $e) {
            return new VerificationResult(
                success: $this->onFailure === 'pass',
                errorCodes: ['transport-error'],
                raw: ['exception' => $e::class, 'message' => $e->getMessage()],
            );
        }

        return $this->mapAssessment($assessment, $expected);
    }

    public function responseFieldName(): string
    {
        return 'g-recaptcha-response';
    }

    public function scriptUrl(): string
    {
        $url = 'https://www.google.com/recaptcha/enterprise.js';

        return ($this->config['mode'] ?? 'score') === 'score'
            ? $url . '?render=' . $this->siteKey()
            : $url;
    }

    public function siteKey(): string
    {
        return (string) ($this->config['site_key'] ?? '');
    }

    protected function mapAssessment(Assessment $assessment, ?string $expected): VerificationResult
    {
        $props = $assessment->getTokenProperties();

        $success = $props?->getValid() ?? false;
        $errorCodes = [];

        if (!$success && $props !== null) {
            $errorCodes[] = str_replace('_', '-', strtolower(InvalidReason::name($props->getInvalidReason())));
        }

        $action = $props?->getAction();

        if ($success && $expected !== null && $action !== $expected) {
            $success = false;
            $errorCodes[] = 'action-mismatch';
        }

        $createTime = $props?->getCreateTime();
        $hostname = $props?->getHostname();

        return new VerificationResult(
            success: $success,
            score: $assessment->getRiskAnalysis()?->getScore(),
            action: $action !== null && $action !== '' ? $action : null,
            hostname: $hostname !== null && $hostname !== '' ? $hostname : null,
            challengedAt: $createTime !== null
            ? CarbonImmutable::createFromTimestampUTC($createTime->getSeconds())
            : null,
            errorCodes: $errorCodes,
            raw: (array) json_decode($assessment->serializeToJsonString(), true),
            threshold: (float) ($this->config['threshold'] ?? 0.5),
        );
    }
}
