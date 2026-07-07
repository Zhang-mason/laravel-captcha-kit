<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/guarded', fn () => response()->json(['ok' => true]))
        ->middleware('captcha');
});

it('lets verified requests through', function () {
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    $this->postJson('/guarded', ['g-recaptcha-response' => 'the-token'])
        ->assertOk()
        ->assertJson(['ok' => true]);
});

it('rejects failed verifications with a validation error', function () {
    Http::fake(['www.google.com/*' => Http::response([
        'success' => false,
        'error-codes' => ['invalid-input-response'],
    ])]);

    $this->postJson('/guarded', ['g-recaptcha-response' => 'bad-token'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('g-recaptcha-response');
});

it('rejects requests missing the token field', function () {
    Http::fake(['www.google.com/*' => Http::response([
        'success' => false,
        'error-codes' => ['missing-input-response'],
    ])]);

    $this->postJson('/guarded')->assertStatus(422);
});

it('can target a specific driver via middleware parameters', function () {
    Route::post('/turnstile-guarded', fn () => response()->json(['ok' => true]))
        ->middleware('captcha:turnstile');

    Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

    $this->postJson('/turnstile-guarded', ['cf-turnstile-response' => 'the-token'])
        ->assertOk();
});

it('skips verification in skipped environments', function () {
    config(['captcha.skip_environments' => ['testing']]);
    Http::fake();

    $this->postJson('/guarded')->assertOk();

    Http::assertNothingSent();
});
