<?php

namespace Mason\Captcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Mason\Captcha\CaptchaManager;

class CaptchaRule implements ValidationRule
{
    public function __construct(
        protected ?string $driverName = null,
        protected ?string $expectedAction = null,
    ) {}

    public static function driver(string $driver): static
    {
        return new static($driver);
    }

    public function action(string $action): static
    {
        $this->expectedAction = $action;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $manager = app('captcha');

        if ($manager instanceof CaptchaManager && $manager->shouldSkip()) {
            return;
        }

        $driver = $manager->driver($this->driverName);

        if ($this->expectedAction !== null && method_exists($driver, 'expectAction')) {
            $driver->expectAction($this->expectedAction);
        }

        $result = $driver->verify(is_string($value) ? $value : '', request()?->ip());

        if ($result->failed()) {
            $fail('captcha::validation.failed')->translate();
        }
    }
}
