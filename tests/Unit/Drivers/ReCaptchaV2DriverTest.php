<?php

use Illuminate\Support\Facades\Http;
use Mason\Captcha\Facades\Captcha;

it('maps a successful siteverify response', function () {
    Http::fake([
        'www.google.com/recaptcha/api/siteverify' => Http::response([
            'success' => true,
            'challenge_ts' => '2026-07-07T00:00:00Z',
            'hostname' => 'example.test',
        ]),
    ]);

    $result = Captcha::driver('recaptcha_v2')->verify('the-token', '1.2.3.4');

    expect($result->passed())->toBeTrue()
        ->and($result->hostname)->toBe('example.test')
        ->and($result->challengedAt?->toIso8601ZuluString())->toBe('2026-07-07T00:00:00Z')
        ->and($result->raw['success'])->toBeTrue();
});

it('sends secret, response and remoteip as form params', function () {
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    Captcha::driver('recaptcha_v2')->verify('the-token', '1.2.3.4');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'the-token'
            && $request['remoteip'] === '1.2.3.4';
    });
});

it('omits remoteip when no ip is given', function () {
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    Captcha::driver('recaptcha_v2')->verify('the-token');

    Http::assertSent(fn ($request) => ! isset($request['remoteip']));
});

it('maps a rejected token with its error codes', function () {
    Http::fake([
        'www.google.com/*' => Http::response([
            'success' => false,
            'error-codes' => ['invalid-input-response', 'timeout-or-duplicate'],
        ]),
    ]);

    $result = Captcha::driver('recaptcha_v2')->verify('bad-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['invalid-input-response', 'timeout-or-duplicate']);
});

it('fails closed when siteverify is unreachable', function () {
    Http::fake(['www.google.com/*' => Http::response('server error', 500)]);

    $result = Captcha::driver('recaptcha_v2')->verify('the-token');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['transport-error']);
});

it('can fail open when configured', function () {
    config(['captcha.on_failure' => 'pass']);
    Http::fake(['www.google.com/*' => Http::response('server error', 500)]);

    $result = Captcha::driver('recaptcha_v2')->verify('the-token');

    expect($result->passed())->toBeTrue()
        ->and($result->errorCodes)->toBe(['transport-error']);
});

it('exposes frontend metadata', function () {
    $driver = Captcha::driver('recaptcha_v2');

    expect($driver->responseFieldName())->toBe('g-recaptcha-response')
        ->and($driver->scriptUrl())->toBe('https://www.google.com/recaptcha/api.js')
        ->and($driver->siteKey())->toBe('test-site-key');
});
