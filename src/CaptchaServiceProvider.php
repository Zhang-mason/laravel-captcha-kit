<?php

namespace Mason\Captcha;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Mason\Captcha\Http\Middleware\VerifyCaptcha;
use Mason\Captcha\View\Components\Captcha as CaptchaComponent;

class CaptchaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/captcha.php', 'captcha');

        $this->app->singleton('captcha', fn ($app) => new CaptchaManager($app));
        $this->app->alias('captcha', CaptchaManager::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'captcha');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'captcha');

        Blade::component('captcha', CaptchaComponent::class);

        $this->app['router']->aliasMiddleware('captcha', VerifyCaptcha::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/captcha.php' => config_path('captcha.php'),
            ], 'captcha-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/captcha'),
            ], 'captcha-views');

            $this->publishes([
                __DIR__.'/../lang' => $this->app->langPath('vendor/captcha'),
            ], 'captcha-lang');
        }
    }
}
