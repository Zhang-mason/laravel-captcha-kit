<?php

namespace Mason\Captcha\Tests;

use Illuminate\Http\Client\Factory as HttpFactory;
use Mason\Captcha\CaptchaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [CaptchaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        // Share one HTTP factory between the Http facade (which the tests
        // fake) and the drivers (which resolve it from the container).
        $app->singleton(HttpFactory::class);

        $app['config']->set('captcha.skip_environments', []);

        foreach (['recaptcha_v2', 'recaptcha_v3', 'hcaptcha', 'turnstile'] as $driver) {
            $app['config']->set("captcha.drivers.{$driver}.site_key", 'test-site-key');
            $app['config']->set("captcha.drivers.{$driver}.secret_key", 'test-secret-key');
        }
    }
}
