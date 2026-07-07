<?php

namespace Mason\Captcha\Drivers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\VerificationResult;
use Throwable;

abstract class AbstractDriver implements CaptchaDriver
{
    /**
     * @param  array<string, mixed>  $config  The driver's entry from captcha.drivers.*
     */
    public function __construct(
        protected HttpFactory $http,
        protected array $config,
        protected int $timeout = 5,
        protected int $retry = 1,
        protected string $onFailure = 'fail',
    ) {}

    public function verify(string $token, ?string $ip = null): VerificationResult
    {
        try {
            $response = $this->http
                ->asForm()
                ->timeout($this->timeout)
                ->retry($this->retry + 1, 100)
                ->post($this->endpoint(), $this->payload($token, $ip))
                ->throw();
        } catch (Throwable $e) {
            return $this->transportFailure($e);
        }

        return $this->mapResponse((array) $response->json());
    }

    public function siteKey(): string
    {
        return (string) ($this->config['site_key'] ?? '');
    }

    abstract protected function endpoint(): string;

    /**
     * @param  array<string, mixed>  $data
     */
    abstract protected function mapResponse(array $data): VerificationResult;

    /**
     * @return array<string, string>
     */
    protected function payload(string $token, ?string $ip): array
    {
        return array_filter([
            'secret' => (string) ($this->config['secret_key'] ?? ''),
            'response' => $token,
            'remoteip' => $ip,
        ], fn ($value) => $value !== null && $value !== '');
    }

    protected function challengedAt(array $data): ?CarbonImmutable
    {
        return isset($data['challenge_ts'])
            ? CarbonImmutable::parse($data['challenge_ts'])
            : null;
    }

    protected function transportFailure(Throwable $e): VerificationResult
    {
        return new VerificationResult(
            success: $this->onFailure === 'pass',
            errorCodes: ['transport-error'],
            raw: ['exception' => $e::class, 'message' => $e->getMessage()],
        );
    }
}
