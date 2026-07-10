<?php

namespace Mason\Captcha\Facades;

use Illuminate\Support\Facades\Facade;
use Mason\Captcha\CaptchaManager;
use Mason\Captcha\Contracts\CaptchaDriver;
use Mason\Captcha\Testing\CaptchaFake;
use Mason\Captcha\VerificationResult;

/**
 * @method static VerificationResult verify(string $token, ?string $ip = null)
 * @method static CaptchaDriver driver(?string $driver = null)
 * @method static string responseFieldName()
 * @method static string scriptUrl()
 * @method static string siteKey()
 * @method static bool shouldSkip()
 * @method static \Mason\Captcha\CaptchaManager extend(string $driver, \Closure $callback)
 *
 * @see CaptchaManager
 */
class Captcha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'captcha';
    }

    /**
     * Swap the manager for a fake that never performs HTTP verification.
     */
    public static function fake(): CaptchaFake
    {
        $fake = new CaptchaFake(
            (float) static::$app['config']->get('captcha.drivers.recaptcha_v3.threshold', 0.5),
        );

        static::swap($fake);

        return $fake;
    }
}
