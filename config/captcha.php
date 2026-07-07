<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Captcha Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "recaptcha_v2", "recaptcha_v3", "hcaptcha", "turnstile"
    |
    */

    'default' => env('CAPTCHA_DRIVER', 'recaptcha_v2'),

    /*
    |--------------------------------------------------------------------------
    | Driver Configuration
    |--------------------------------------------------------------------------
    */

    'drivers' => [

        'recaptcha_v2' => [
            'site_key' => env('RECAPTCHA_V2_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_V2_SECRET_KEY'),
            'mode' => env('RECAPTCHA_V2_MODE', 'checkbox'), // checkbox | invisible
            'theme' => 'light', // light | dark
            'size' => 'normal', // normal | compact
        ],

        'recaptcha_v3' => [
            'site_key' => env('RECAPTCHA_V3_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_V3_SECRET_KEY'),
            // Scores below this threshold are treated as failures.
            'threshold' => (float) env('RECAPTCHA_V3_THRESHOLD', 0.5),
            // Expected action; null skips action verification. Can be
            // overridden per call via CaptchaRule::driver(...)->action(...).
            'action' => null,
        ],

        'hcaptcha' => [
            'site_key' => env('HCAPTCHA_SITE_KEY'),
            'secret_key' => env('HCAPTCHA_SECRET_KEY'),
            'mode' => env('HCAPTCHA_MODE', 'checkbox'), // checkbox | invisible
            'theme' => 'light',
        ],

        'turnstile' => [
            'site_key' => env('TURNSTILE_SITE_KEY'),
            'secret_key' => env('TURNSTILE_SECRET_KEY'),
            'mode' => env('TURNSTILE_MODE', 'managed'), // managed | non-interactive | invisible
            'theme' => 'auto',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => 5, // seconds
        'retry' => 1, // additional attempts after the first failure
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Outage Behaviour
    |--------------------------------------------------------------------------
    |
    | What to do when the siteverify endpoint cannot be reached.
    | "fail" blocks the request (fail-closed), "pass" lets it through.
    |
    */

    'on_failure' => env('CAPTCHA_ON_FAILURE', 'fail'),

    /*
    |--------------------------------------------------------------------------
    | Skipped Environments
    |--------------------------------------------------------------------------
    |
    | The rule and middleware skip verification entirely in these
    | environments. Prefer Captcha::fake() in tests when possible.
    |
    */

    'skip_environments' => ['testing'],

];
