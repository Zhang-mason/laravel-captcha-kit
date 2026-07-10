<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mason\Captcha\Rules\CaptchaRule;

function validateToken(mixed $token): Illuminate\Validation\Validator
{
    return Validator::make(
        ['g-recaptcha-response' => $token],
        ['g-recaptcha-response' => ['required', new CaptchaRule]],
    );
}

it('passes when the captcha verifies', function () {
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    expect(validateToken('the-token')->passes())->toBeTrue();
});

it('fails when the captcha is rejected', function () {
    Http::fake(['www.google.com/*' => Http::response([
        'success' => false,
        'error-codes' => ['invalid-input-response'],
    ])]);

    $validator = validateToken('bad-token');

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('g-recaptcha-response'))
        ->toBe(__('captcha::validation.failed'));
});

it('can target a specific driver', function () {
    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $validator = Validator::make(
        ['cf-turnstile-response' => 'the-token'],
        ['cf-turnstile-response' => ['required', CaptchaRule::driver('turnstile')]],
    );

    expect($validator->passes())->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'challenges.cloudflare.com'));
});

it('skips verification in skipped environments', function () {
    config(['captcha.skip_environments' => ['testing']]);
    Http::fake();

    expect(validateToken('anything')->passes())->toBeTrue();

    Http::assertNothingSent();
});
