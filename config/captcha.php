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
