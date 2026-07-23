<?php

namespace Mason\Captcha\Tests;

use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Testing\MockTransport;
use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
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

        $app['config']->set('captcha.drivers.recaptcha_enterprise.site_key', 'test-site-key');
        $app['config']->set('captcha.drivers.recaptcha_enterprise.project_id', 'test-project');
    }

    /**
     * Bind a real Enterprise client backed by a mock transport (the client
     * class is final, so the SDK cannot be mocked directly). Queue responses
     * on the returned transport.
     */
    protected function fakeEnterpriseTransport(): MockTransport
    {
        $transport = new MockTransport();

        $this->app->instance(
            RecaptchaEnterpriseServiceClient::class,
            new RecaptchaEnterpriseServiceClient([
                'transport' => $transport,
                'credentials' => $this->createMock(CredentialsWrapper::class),
            ]),
        );

        return $transport;
    }
}
