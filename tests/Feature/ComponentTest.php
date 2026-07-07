<?php

use Illuminate\View\ViewException;

it('renders the recaptcha v2 checkbox widget', function () {
    $this->blade('<x-captcha />')
        ->assertSee('class="g-recaptcha"', false)
        ->assertSee('data-sitekey="test-site-key"', false)
        ->assertSee('https://www.google.com/recaptcha/api.js', false);
});

it('renders the invisible variant when configured', function () {
    config(['captcha.drivers.recaptcha_v2.mode' => 'invisible']);

    $this->blade('<x-captcha />')
        ->assertSee('data-size="invisible"', false)
        ->assertSee('grecaptcha.execute()', false);
});

it('accepts theme and size overrides', function () {
    $this->blade('<x-captcha theme="dark" size="compact" />')
        ->assertSee('data-theme="dark"', false)
        ->assertSee('data-size="compact"', false);
});

it('throws for drivers without a widget yet', function () {
    // Blade wraps component exceptions in a ViewException.
    $this->blade('<x-captcha driver="turnstile" />');
})->throws(ViewException::class, 'No <x-captcha> widget for driver [turnstile] yet');
