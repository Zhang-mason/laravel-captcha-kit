<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Mason\Captcha\Facades\Captcha;
use Mason\Captcha\Rules\CaptchaRule;

it('passes verifications without any http traffic', function () {
    Http::fake();
    $fake = Captcha::fake();

    expect(Captcha::verify('any-token')->passed())->toBeTrue();

    $fake->assertVerified();
    Http::assertNothingSent();
});

it('can fail all verifications', function () {
    Captcha::fake()->failing();

    expect(Captcha::verify('any-token')->failed())->toBeTrue();
});

it('can simulate v3 scores against the configured threshold', function () {
    $fake = Captcha::fake();

    $fake->scoring(0.3);
    expect(Captcha::verify('any-token')->failed())->toBeTrue();

    $fake->scoring(0.9);
    expect(Captcha::verify('any-token')->passed())->toBeTrue();
});

it('works through the validation rule', function () {
    $fake = Captcha::fake();

    $validator = Validator::make(
        ['g-recaptcha-response' => 'any-token'],
        ['g-recaptcha-response' => ['required', new CaptchaRule]],
    );

    expect($validator->passes())->toBeTrue();

    $fake->assertVerifiedTimes(1);
    $fake->assertVerified(fn (string $token) => $token === 'any-token');
});

it('works through the middleware', function () {
    Route::post('/guarded', fn () => response()->json(['ok' => true]))
        ->middleware('captcha');

    Captcha::fake()->failing();

    $this->postJson('/guarded', ['g-recaptcha-response' => 'any-token'])
        ->assertStatus(422);
});

it('can assert nothing was verified', function () {
    Captcha::fake()->assertNothingVerified();
});
